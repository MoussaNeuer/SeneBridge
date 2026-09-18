<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Appointment;
use App\Repositories\AppointmentRepository;
use App\Support\Audit;
use App\Support\Log;

final class AppointmentService
{
    private AppointmentRepository $appointments;

    public function __construct(?AppointmentRepository $appointments = null)
    {
        $this->appointments = $appointments ?? new AppointmentRepository();
    }

    /**
     * @return array{appointment: array<string, mixed>|null, error: ?string}
     */
    public function create(array $data, array $client, array $user): array
    {
        if (($data['counselor_id'] ?? '') === '') {
            return ['appointment' => null, 'error' => 'Un conseiller doit être sélectionné.'];
        }

        $time = (string) ($data['requested_time'] ?? '');
        if (!preg_match('/^\d{2}:\d{2}$/', $time)) {
            return ['appointment' => null, 'error' => 'L\'heure doit être au format HH:MM.'];
        }

        [$hours, $minutes] = array_map('intval', explode(':', $time));
        if ($hours > 23 || $minutes > 59) {
            return ['appointment' => null, 'error' => 'L\'heure demandée est invalide.'];
        }

        $date = (string) ($data['requested_date'] ?? '');
        if ($date < date('Y-m-d')) {
            return ['appointment' => null, 'error' => 'La date demandée est déjà passée.'];
        }

        try {
            $appointment = Appointment::create([
                'project_id' => $data['project_id'] ?? null,
                'client_id' => (int) $client['id'],
                'counselor_id' => (int) $data['counselor_id'],
                'requested_date' => $data['requested_date'],
                'requested_time' => $data['requested_time'],
                'motive' => trim((string) $data['motive']),
                'status' => 'demande',
                'created_by' => (int) $user['id'],
            ]);
        } catch (\Throwable $e) {
            Log::error('Création de rendez-vous impossible', ['error' => $e->getMessage()]);

            return ['appointment' => null, 'error' => 'Impossible d\'enregistrer le rendez-vous.'];
        }

        if ($appointment === null) {
            return ['appointment' => null, 'error' => 'Impossible d\'enregistrer le rendez-vous.'];
        }

        Audit::log('appointments.created', 'appointments', (int) $appointment['id'], [], [
            'client_id' => (int) $appointment['client_id'],
            'requested_date' => $appointment['requested_date'],
            'requested_time' => $appointment['requested_time'],
        ]);

        // Le conseiller désigné est prévenu qu'une demande est en attente.
        if ($appointment['counselor_id'] !== null) {
            Notifier::push(
                (int) $appointment['counselor_id'],
                Notifier::TYPE_APPOINTMENT,
                'Nouvelle demande de rendez-vous',
                sprintf('%s à %s', $appointment['requested_date'], $appointment['requested_time']),
                $appointment['project_id'] !== null ? (int) $appointment['project_id'] : null
            );
        }

        return ['appointment' => $appointment, 'error' => null];
    }

    public function confirm(int $appointmentId, array $user): ?array
    {
        return $this->transition($appointmentId, 'confirme', $user);
    }

    public function cancel(int $appointmentId, array $user): ?array
    {
        return $this->transition($appointmentId, 'annule', $user);
    }

    public function complete(int $appointmentId, array $user): ?array
    {
        return $this->transition($appointmentId, 'termine', $user);
    }

    private function transition(int $appointmentId, string $status, array $user): ?array
    {
        $allowed = ['confirme', 'annule', 'termine'];
        $from = ['demande', 'confirme', 'termine'];

        if (!in_array($status, $allowed, true)) {
            return null;
        }

        $appointment = $this->appointments->find($appointmentId);

        if ($appointment === null || !in_array($appointment['status'], $from, true)) {
            return $appointment;
        }

        Appointment::update($appointmentId, ['status' => $status]);

        Audit::log('appointments.' . $status, 'appointments', $appointmentId, [
            'from' => $appointment['status'],
            'to' => $status,
        ], ['appointment_id' => $appointmentId]);

        $label = ['confirme' => 'confirmé', 'annule' => 'annulé', 'termine' => 'terminé'][$status];

        Notifier::push(
            (int) $appointment['client_id'],
            Notifier::TYPE_APPOINTMENT,
            sprintf('Votre rendez-vous du %s : %s', $appointment['requested_date'], $label),
            sprintf('%s à %s', $appointment['requested_date'], $appointment['requested_time']),
            $appointment['project_id'] !== null ? (int) $appointment['project_id'] : null
        );

        return $this->appointments->find($appointmentId);
    }
}