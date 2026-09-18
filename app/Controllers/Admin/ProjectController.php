<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\Controller;
use App\Models\Project;
use App\Models\ProjectStep;
use App\Policies\ProjectPolicy;
use App\Repositories\ProjectRepository;
use App\Repositories\UserRepository;
use App\Services\ProjectService;
use App\Services\WorkflowService;
use App\Support\App;
use App\Support\Gate;
use App\Support\Response;
use App\Validators\ProjectValidator;

final class ProjectController extends Controller
{
    private ProjectRepository $projects;
    private UserRepository $users;
    private ProjectService $service;

    public function __construct()
    {
        parent::__construct();
        $this->projects = new ProjectRepository();
        $this->users = new UserRepository();
        $this->service = new ProjectService($this->projects);
    }

    public function index(): Response
    {
        $user = App::user();
        $filters = [
            'status' => (string) $this->request->query('status', ''),
            'type' => (string) $this->request->query('type', ''),
        ];
        $page = max(1, (int) $this->request->query('page', 1));

        $projects = $this->projects->listManaged($filters, 20, $page);

        // Un conseiller ne voit que ses propres dossiers (moindre privilège).
        if (Gate::isRole($user, 'counselor')) {
            $own = [];
            foreach ($projects['items'] as $project) {
                if ((int) $project['counselor_id'] === (int) $user['id']) {
                    $own[] = $project;
                }
            }
            $projects['items'] = $own;
            $projects['total'] = count($own);
        }

        return Response::view('admin/projects/index', [
            'user' => $user,
            'pagination' => $projects,
            'filters' => $filters,
            'stats' => $this->projects->stats(),
        ]);
    }

    public function create(string $publicId = ''): Response
    {
        $user = App::user();
        $prefillClientId = null;
        $clientPublicId = $publicId !== '' ? $publicId : (string) $this->request->query('client', '');

        if ($clientPublicId !== '') {
            $client = (new UserRepository())->findByPublicId($clientPublicId);
            if ($client !== null && $client['role_id'] === ((int) (\App\Models\Role::findByName('client')['id'] ?? 0))) {
                $prefillClientId = (int) $client['id'];
            }
        }

        return Response::view('admin/projects/create', [
            'user' => $user,
            'clients' => $this->users->paginateByRole('client', 500),
            'counselors' => $this->users->listCounselors(),
            'prefillClientId' => $prefillClientId,
        ]);
    }

    public function store(): Response
    {
        $data = $this->request->only([
            'client_id', 'name', 'type', 'locality', 'description', 'budget',
            'currency', 'counselor_id', 'start_date', 'expected_end_date',
        ]);
        $validation = ProjectValidator::register($data);

        if (!$validation->passes()) {
            return $this->backWithErrors($validation->errors(), $data);
        }

        // Le client doit exister et être du rôle client.
        $client = $this->users->findById((int) $data['client_id']);
        $clientRoleId = (int) (\App\Models\Role::findByName('client')['id'] ?? 0);

        if ($client === null || (int) $client['role_id'] !== $clientRoleId) {
            App::flash('error', 'Le client sélectionné est invalide.');
            App::remember($data);

            return Response::redirectBack();
        }

        $result = $this->service->create($data, App::user());

        if ($result['error'] !== null || $result['project'] === null) {
            App::flash('error', $result['error']);
            App::remember($data);

            return Response::redirectBack();
        }

        App::flash('success', sprintf('Dossier %s créé. Le client a été notifié.', $result['project']['reference']));

        return Response::redirect(route('admin.projects.show', ['publicId' => $result['project']['public_id']]));
    }

    public function show(string $publicId): Response
    {
        $user = App::user();
        $project = ProjectPolicy::project($publicId);

        if ($project === null || !ProjectPolicy::view($user, $project)) {
            return Response::notFound('Ce dossier est introuvable ou inaccessible.');
        }

        $detail = $this->projects->detail((int) $project['id']);
        $detail['steps_count'] = (int) ($detail['steps_count'] ?? 0);
        $detail['steps_completed'] = (int) ($detail['steps_completed'] ?? 0);

        $documents = \App\Models\Document::where(['project_id' => (int) $project['id']], [['created_at', 'DESC']]);
        $media = \App\Models\Media::where(['project_id' => (int) $project['id']], [['created_at', 'DESC']]);

        return Response::view('admin/projects/show', [
            'user' => $user,
            'project' => $detail,
            'steps' => $this->projects->steps((int) $project['id']),
            'properties' => $this->projects->properties((int) $project['id']),
            'documents' => $documents,
            'media' => $media,
            'history' => $this->projects->recentActivity((int) $project['id'], 20),
            'progress' => WorkflowService::progress($detail),
            'currentStep' => $this->projects->currentStep((int) $project['id']),
            'counselors' => $this->users->listCounselors(),
            'canManage' => ProjectPolicy::manage($user, $project),
            'canSteps' => ProjectPolicy::updateSteps($user, $project),
            'canUpload' => $user && ProjectPolicy::view($user, $project),
        ]);
    }

    public function edit(string $publicId): Response
    {
        $user = App::user();
        $project = ProjectPolicy::project($publicId);

        if ($project === null || !ProjectPolicy::manage($user, $project)) {
            return Response::notFound('Ce dossier est introuvable ou inaccessible.');
        }

        return Response::view('admin/projects/edit', [
            'user' => $user,
            'project' => $this->projects->detail((int) $project['id']),
            'counselors' => $this->users->listCounselors(),
        ]);
    }

    public function update(string $publicId): Response
    {
        $user = App::user();
        $project = ProjectPolicy::project($publicId);

        if ($project === null || !ProjectPolicy::manage($user, $project)) {
            return Response::notFound('Ce dossier est introuvable ou inaccessible.');
        }

        $data = $this->request->only([
            'name', 'type', 'locality', 'description', 'budget', 'currency',
            'start_date', 'expected_end_date', 'status',
        ]);
        $validation = ProjectValidator::update($data);

        if (!$validation->passes()) {
            return $this->backWithErrors($validation->errors(), $data);
        }

        $fields = [
            'name' => trim((string) $data['name']),
            'type' => $data['type'],
            'locality' => trim((string) ($data['locality'] ?? '')) !== '' ? trim((string) $data['locality']) : null,
            'description' => trim((string) ($data['description'] ?? '')) !== '' ? trim((string) $data['description']) : null,
            'budget' => isset($data['budget']) && trim((string) $data['budget']) !== '' ? (float) $data['budget'] : null,
            'currency' => $data['currency'] ?? 'XOF',
            'start_date' => ($data['start_date'] ?? '') !== '' ? $data['start_date'] : null,
            'expected_end_date' => ($data['expected_end_date'] ?? '') !== '' ? $data['expected_end_date'] : null,
        ];

        Project::update((int) $project['id'], $fields);

        if (isset($data['status']) && $data['status'] !== $project['status']) {
            $this->service->updateStatus((int) $project['id'], (string) $data['status'], $user);
        }

        \App\Support\Audit::log('projects.updated', 'projects', (int) $project['id'], [], ['project_id' => (int) $project['id']]);
        App::flash('success', 'Le dossier a été mis à jour.');

        return Response::redirect(route('admin.projects.show', ['publicId' => $project['public_id']]));
    }

    public function status(string $publicId): Response
    {
        $user = App::user();
        $project = ProjectPolicy::project($publicId);

        if ($project === null || !ProjectPolicy::manage($user, $project)) {
            return Response::notFound('Ce dossier est introuvable ou inaccessible.');
        }

        $status = (string) $this->request->post('status', '');

        if (!in_array($status, ['en_cours', 'bloque', 'termine', 'archive'], true)) {
            App::flash('error', 'Statut invalide.');

            return Response::redirectBack();
        }

        $this->service->updateStatus((int) $project['id'], $status, $user);
        App::flash('success', sprintf('Statut du dossier %s mis à jour.', $project['reference']));

        return Response::redirect(route('admin.projects.show', ['publicId' => $project['public_id']]));
    }

    public function counselor(string $publicId): Response
    {
        $user = App::user();
        $project = ProjectPolicy::project($publicId);

        if ($project === null || !ProjectPolicy::manage($user, $project)) {
            return Response::notFound('Ce dossier est introuvable ou inaccessible.');
        }

        $counselorId = $this->request->post('counselor_id', null);
        $counselorId = $counselorId !== null && $counselorId !== '' ? (int) $counselorId : null;

        if ($counselorId !== null && (new UserRepository())->findById($counselorId) === null) {
            App::flash('error', 'Conseiller introuvable.');

            return Response::redirectBack();
        }

        $this->service->assignCounselor(
            (int) $project['id'],
            $counselorId,
            $user,
            $this->request->post('note', '')
        );
        App::flash('success', 'Conseiller affecté au dossier.');

        return Response::redirect(route('admin.projects.show', ['publicId' => $project['public_id']]));
    }

    /**
     * Transition d'étape du workflow (statut, commentaire, échéance, responsable).
     */
    public function stepTransition(string $publicId): Response
    {
        $user = App::user();
        $project = ProjectPolicy::project($publicId);

        if ($project === null || !ProjectPolicy::updateSteps($user, $project)) {
            return Response::notFound('Action non autorisée sur ce dossier.');
        }

        $step = ProjectStep::findByPublicId((string) $this->request->post('step_id', ''));
        $toStatus = (string) $this->request->post('to_status', '');
        $comment = (string) $this->request->post('comment', '');

        if ($step === null || (int) $step['project_id'] !== (int) $project['id']) {
            App::flash('error', 'Étape introuvable.');

            return Response::redirectBack();
        }

        if ($toStatus === 'comment') {
            $result = WorkflowService::commentStep((int) $step['id'], $user, $comment);
            App::flash($result['ok'] ? 'success' : 'error', $result['error'] ?? 'Commentaire ajouté.');

            return Response::redirect(route('admin.projects.show', ['publicId' => $project['public_id']]));
        }

        $result = WorkflowService::updateStep(
            (int) $step['id'],
            $user,
            $toStatus,
            $comment,
            $this->request->post('responsible_id', null) !== '' && $this->request->post('responsible_id', null) !== null
                ? (int) $this->request->post('responsible_id')
                : null,
            (string) $this->request->post('due_date', '')
        );

        App::flash($result['ok'] ? 'success' : 'error', $result['error'] ?? 'Étape mise à jour et le client a été notifié.');

        return Response::redirect(route('admin.projects.show', ['publicId' => $project['public_id']]));
    }
}