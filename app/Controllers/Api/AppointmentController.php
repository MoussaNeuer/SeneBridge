<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Models\Appointment;
use App\Repositories\AppointmentRepository;
use App\Support\Audit;
use App\Support\Database;
use App\Support\Response;
use App\Support\Validator;

final class AppointmentController extends ApiController
{
    private AppointmentRepository $appointments;

    public function __construct()
    {
        parent::__construct();
        $this->appointments = new AppointmentRepository();
    }

    /**
     * GET /api/v1/appointments
     */
    public function index(): Response
    {
        if (!$this->isStaff()) {
            $items = $this->appointments->forClient($this->id());

            return $this->ok(['items' => $items, 'total' => count($items), 'per_page' => count($items), 'page' => 1, 'last_page' => 1]);
        }

        $filters = ['status' => (string) $this->request->query('status', '')];
        ['page' => $page, 'per_page' => $perPage] = $this->paging();

        return $this->page($this->appointments->listManaged(null, $filters, $perPage, $page));
    }

    /**
     * GET /api/v1/appointments/{publicId}
     */
    public function show(string $publicId): Response
    {
        $appointment = Appointment::findByPublicId($publicId);

        if ($appointment === null || !$this->owns(isset($appointment['client_id']) ? (int) $appointment['client_id'] : null)) {
            return $this->notFound('Rendez-vous introuvable.');
        }

        $detail = $this->appointments->detail((int) $appointment['id']);

        if ($detail === null) {
            return $this->notFound('Rendez-vous introuvable.');
        }

        return $this->ok($detail);
    }

    /**
     * POST /api/v1/appointments — demande de rendez-vous.
     */
    public function store(): Response
    {
        if (!$this->emailVerified()) {
            return $this->fail('Votre adresse e-mail doit être vérifiée.', 403);
        }

        $data = $this->request->only([
            'project_id', 'counselor_id', 'requested_date', 'requested_time', 'motive', 'notes',
        ]);

        $validation = Validator::make($data, [
            'counselor_id' => 'required|int',
            'requested_date' => 'required|date',
            'requested_time' => 'required|string|max:5',
            'motive' => 'required|string|max:500',
            'project_id' => 'nullable|int',
            'notes' => 'nullable|string|max:1000',
        ]);

        if (!$validation->passes()) {
            return $this->fail($validation->firstMessage() ?? 'Données invalides.', 422, $validation->errors());
        }

        $clientId = $this->id();

        if (!$this->isStaff()) {
            // Le client ne réserve que pour lui-même et sur ses propres dossiers.
            if (isset($data['project_id']) && (int) $data['project_id'] > 0 && !$this->ownsProject((int) $data['project_id'])) {
                return $this->forbidden('Ce dossier ne vous appartient pas.');
            }
        } else {
            $data['project_id'] = isset($data['project_id']) && (int) $data['project_id'] > 0 ? (int) $data['project_id'] : null;

            if ($this->isRole('counselor') && (!isset($data['counselor_id']) || (int) $data['counselor_id'] > 0 === false)) {
                $data['counselor_id'] = $this->id();
            }
        }

        // Le conseiller cible doit exister et être actif.
        if (!$this->validCounselor((int) $data['counselor_id'])) {
            return $this->fail('Conseiller invalide.', 422, ['counselor_id' => 'Conseiller introuvable ou inactif.']);
        }

        $appointment = Appointment::create([
            'client_id' => $clientId,
            'counselor_id' => (int) $data['counselor_id'],
            'project_id' => $data['project_id'] ?? null,
            'requested_date' => (string) $data['requested_date'],
            'requested_time' => (string) $data['requested_time'],
            'motive' => trim((string) $data['motive']),
            'notes' => isset($data['notes']) && trim((string) $data['notes']) !== '' ? trim((string) $data['notes']) : null,
            'status' => 'demande',
            'created_by' => $clientId,
        ]);

        if ($appointment === null) {
            return $this->fail('Impossible d\'enregistrer la demande.', 500);
        }

        Audit::log('appointments.requested', 'appointments', (int) $appointment['id'], [], [
            'by' => ($this->authUser['first_name'] ?? '') . ' ' . ($this->authUser['last_name'] ?? ''),
        ]);

        return $this->created($appointment);
    }

    /**
     * POST /api/v1/appointments/{publicId}/cancel
     */
    public function cancel(string $publicId): Response
    {
        $appointment = Appointment::findByPublicId($publicId);

        if ($appointment === null || !$this->owns(isset($appointment['client_id']) ? (int) $appointment['client_id'] : null)) {
            return $this->notFound('Rendez-vous introuvable.');
        }

        $status = (string) ($appointment['status'] ?? '');
        if ($status !== 'demande' && $status !== 'confirme') {
            return $this->fail('Ce rendez-vous ne peut plus être annulé.', 422);
        }

        Appointment::update((int) $appointment['id'], ['status' => 'annule']);

        Audit::log('appointments.cancelled', 'appointments', (int) $appointment['id'], ['status' => $status], ['status' => 'annule']);

        $appointment['status'] = 'annule';

        return $this->ok($appointment, 'Rendez-vous annulé.');
    }

    private function isRole(string $role): bool
    {
        return \App\Support\Gate::isRole($this->authUser, $role);
    }

    private function ownsProject(int $projectId): bool
    {
        $row = Database::first('SELECT client_id FROM projects WHERE id = ? LIMIT 1', [$projectId]);

        return $row !== null && (int) $row['client_id'] === $this->id();
    }

    private function validCounselor(int $counselorId): bool
    {
        $roleName = \App\Support\Gate::roleName($this->authUser);

        return Database::scalar(
            'SELECT COUNT(*) FROM users u JOIN roles r ON r.id = u.role_id
             WHERE u.id = ? AND u.status = \'active\' AND r.name = \'counselor\'',
            [$counselorId]
        ) > 0;
    }
}