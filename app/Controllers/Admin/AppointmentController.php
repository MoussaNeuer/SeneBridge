<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\Controller;
use App\Models\Appointment;
use App\Repositories\AppointmentRepository;
use App\Services\AppointmentService;
use App\Support\App;
use App\Support\Response;

final class AppointmentController extends Controller
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
        $filters = ['status' => (string) $this->request->query('status', '')];
        $page = max(1, (int) $this->request->query('page', 1));

        return Response::view('admin/appointments/index', [
            'user' => $user,
            'pagination' => $this->appointments->listManaged($user, $filters, 20, $page),
            'filters' => $filters,
            'stats' => $this->appointments->stats(),
        ]);
    }

    public function show(string $publicId): Response
    {
        $user = App::user();
        $appointment = Appointment::findByPublicId($publicId);

        if ($appointment === null) {
            return Response::notFound('Ce rendez-vous est introuvable.');
        }

        // Un conseiller ne peut pas consulter le rendez-vous d'un confrère.
        if (!\App\Support\Gate::isRole($user, 'admin', 'manager') && (int) ($appointment['counselor_id'] ?? 0) !== (int) $user['id']) {
            return Response::notFound('Ce rendez-vous vous est inaccessible.');
        }

        return Response::view('admin/appointments/show', [
            'user' => $user,
            'appointment' => $this->appointments->detail((int) $appointment['id']),
        ]);
    }

    public function confirm(string $publicId): Response
    {
        return $this->transition($publicId, 'confirm');
    }

    public function cancel(string $publicId): Response
    {
        return $this->transition($publicId, 'cancel');
    }

    public function complete(string $publicId): Response
    {
        return $this->transition($publicId, 'complete');
    }

    private function transition(string $publicId, string $verb): Response
    {
        $user = App::user();
        $appointment = Appointment::findByPublicId($publicId);

        if ($appointment === null) {
            return Response::notFound('Ce rendez-vous est introuvable.');
        }

        if (!\App\Support\Gate::isRole($user, 'admin', 'manager') && (int) ($appointment['counselor_id'] ?? 0) !== (int) $user['id']) {
            return Response::notFound('Ce rendez-vous vous est inaccessible.');
        }

        $method = ['confirm' => 'confirm', 'cancel' => 'cancel', 'complete' => 'complete'][$verb];
        $updated = $this->service->{$method}((int) $appointment['id'], $user);

        $label = ['confirm' => 'confirmé', 'cancel' => 'annulé', 'complete' => 'terminé'][$verb];
        App::flash($updated !== null && $updated['status'] === ['confirm' => 'confirme', 'cancel' => 'annule', 'complete' => 'termine'][$verb] ? 'success' : 'error',
            $updated !== null && $updated['status'] === ['confirm' => 'confirme', 'cancel' => 'annule', 'complete' => 'termine'][$verb]
                ? sprintf('Rendez-vous %s.', $label)
                : 'Impossible de modifier ce rendez-vous.');

        return Response::redirect(route('admin.appointments.show', ['publicId' => $publicId]));
    }
}