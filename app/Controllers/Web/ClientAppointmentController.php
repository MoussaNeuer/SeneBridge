<?php

declare(strict_types=1);

namespace App\Controllers\Web;

use App\Controllers\Controller;
use App\Models\Project;
use App\Repositories\AppointmentRepository;
use App\Repositories\ProjectRepository;
use App\Repositories\UserRepository;
use App\Services\AppointmentService;
use App\Support\App;
use App\Support\Response;
use App\Validators\AppointmentValidator;

final class ClientAppointmentController extends Controller
{
    private AppointmentRepository $appointments;
    private AppointmentService $service;

    public function __construct()
    {
        parent::__construct();
        $this->appointments = new AppointmentRepository();
        $this->service = new AppointmentService($this->appointments);
    }

    public function index(): Response
    {
        $user = App::user();

        return Response::view('client/appointments/index', [
            'appointments' => $this->appointments->forClient((int) $user['id']),
        ]);
    }

    public function create(): Response
    {
        $user = App::user();
        $projects = (new ProjectRepository())->listForClient((int) $user['id']);

        return Response::view('client/appointments/create', [
            'projects' => $projects,
            'counselors' => (new UserRepository())->listCounselors(),
            'prefillProjectId' => (int) $this->request->query('project', 0),
        ]);
    }

    public function store(): Response
    {
        $user = App::user();
        $data = $this->request->only([
            'project_id', 'counselor_id', 'requested_date', 'requested_time', 'motive',
        ]);
        $validation = AppointmentValidator::create($data);

        if (!$validation->passes()) {
            return $this->backWithErrors($validation->errors(), $data);
        }

        // IDOR : le dossier doit appartenir au client connecté.
        $projectId = $data['project_id'] !== null && $data['project_id'] !== '' ? (int) $data['project_id'] : null;
        if ($projectId !== null) {
            $project = Project::find($projectId);
            if ($project === null || (int) $project['client_id'] !== (int) $user['id']) {
                App::flash('error', 'Ce dossier vous est inaccessible pour un rendez-vous.');

                return Response::redirectBack();
            }
        }

        $result = $this->service->create($data, $user, $user);

        if ($result['error'] !== null || $result['appointment'] === null) {
            App::flash('error', $result['error']);

            return Response::redirectBack();
        }

        App::flash('success', 'Demande de rendez-vous enregistrée. Vous serez notifié de sa confirmation.');

        return Response::redirect(route('client.appointments'));
    }

    public function cancel(string $publicId): Response
    {
        $user = App::user();
        $appointment = \App\Models\Appointment::findByPublicId($publicId);

        if ($appointment === null || (int) $appointment['client_id'] !== (int) $user['id']) {
            return Response::notFound('Ce rendez-vous est introuvable.');
        }

        if (!in_array($appointment['status'], ['demande', 'confirme'], true)) {
            App::flash('error', 'Ce rendez-vous ne peut plus être annulé.');

            return Response::redirectBack();
        }

        $this->service->cancel((int) $appointment['id'], $user);
        App::flash('success', 'Rendez-vous annulé.');

        return Response::redirect(route('client.appointments'));
    }
}