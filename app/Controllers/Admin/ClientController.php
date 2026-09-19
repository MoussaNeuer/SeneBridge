<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\Controller;
use App\Models\Project;
use App\Models\Role;
use App\Models\User;
use App\Repositories\ProjectRepository;
use App\Repositories\UserRepository;
use App\Support\App;
use App\Support\Response;

final class ClientController extends Controller
{
    private ProjectRepository $projects;

    public function __construct()
    {
        parent::__construct();
        $this->projects = new ProjectRepository();
    }

    public function index(): Response
    {
        $term = trim((string) $this->request->query('q', ''));
        $page = max(1, (int) $this->request->query('page', 1));

        $clients = $term !== ''
            ? ['items' => (new UserRepository())->searchClients($term), 'total' => 0, 'per_page' => 25, 'page' => 1, 'last_page' => 1]
            : (new UserRepository())->paginateByRole('client', 20, $page);

        return Response::view('admin/clients/index', [
            'user' => App::user(),
            'pagination' => $clients,
            'term' => $term,
        ]);
    }

    public function show(string $publicId): Response
    {
        $user = (new UserRepository())->findByPublicId($publicId);

        if ($user === null || (int) $user['role_id'] !== (int) (Role::findByName('client')['id'] ?? 0)) {
            return Response::notFound('Client introuvable.');
        }

        $projects = Project::where(['client_id' => (int) $user['id']], [['created_at', 'DESC']]);
        $progress = [];

        foreach ($projects as $project) {
            $progress[(int) $project['id']] = [
                'steps_count' => $this->projects->countSteps((int) $project['id']),
                'steps_completed' => $this->projects->countCompletedSteps((int) $project['id']),
            ];
        }

        return Response::view('admin/clients/show', [
            'user' => App::user(),
            'client' => $user,
            'projects' => $projects,
            'progress' => $progress,
            'requests' => \App\Models\ProjectRequest::where(['email' => $user['email']], [['created_at', 'DESC']], 10),
            'roles' => (new \App\Repositories\RoleRepository())->all(),
        ]);
    }
}