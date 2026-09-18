<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Notification;
use App\Models\Project;
use App\Repositories\ProjectRepository;
use App\Support\Audit;
use App\Support\Database;
use App\Support\Str;

/**
 * Création et affectation des dossiers (règles métier centralisées).
 */
final class ProjectService
{
    private ProjectRepository $projects;

    public function __construct(?ProjectRepository $projects = null)
    {
        $this->projects = $projects ?? new ProjectRepository();
    }

    /**
     * @return array{project: array<string, mixed>|null, error: ?string}
     */
    public function create(array $data, array $user): array
    {
        $sequence = $this->projects->nextReferenceSequence();
        $reference = Str::projectReference('SEN', $sequence);

        try {
            $project = Database::transaction(function () use ($data, $reference, $user) {
                $project = Project::create([
                    'client_id' => (int) $data['client_id'],
                    'reference' => $reference,
                    'name' => trim((string) $data['name']),
                    'type' => $data['type'] ?? 'immobilier',
                    'description' => trim((string) ($data['description'] ?? '')) !== '' ? trim((string) $data['description']) : null,
                    'locality' => trim((string) ($data['locality'] ?? '')) !== '' ? trim((string) $data['locality']) : null,
                    'status' => 'en_cours',
                    'budget' => $data['budget'] ?? null,
                    'currency' => $data['currency'] ?? 'XOF',
                    'counselor_id' => $data['counselor_id'] ?? null,
                    'start_date' => $data['start_date'] ?? null,
                    'expected_end_date' => $data['expected_end_date'] ?? null,
                ]);

                WorkflowService::seedSteps(
                    (int) $project['id'],
                    (string) $project['type'],
                    isset($data['counselor_id']) ? (int) $data['counselor_id'] : null
                );

                return $project;
            });
        } catch (\Throwable $e) {
            \App\Support\Log::error('Création de projet impossible', ['error' => $e->getMessage()]);

            return ['project' => null, 'error' => 'Impossible de créer le dossier.'];
        }

        if ($project === null) {
            return ['project' => null, 'error' => 'Impossible de créer le dossier.'];
        }

        Audit::log('projects.created', 'projects', (int) $project['id'], [], [
            'reference' => $reference,
            'client_id' => (int) $project['client_id'],
        ]);

        Notifier::push(
            (int) $project['client_id'],
            Notifier::TYPE_ALERT,
            sprintf('Votre dossier %s a été créé', $reference),
            'Votre projet « ' . $project['name'] . ' » est enregistré. Suivez son avancement en temps réel.',
            (int) $project['id']
        );

        return ['project' => $project, 'error' => null];
    }

    public function assignCounselor(int $projectId, ?int $counselorId, array $user, ?string $note = null): bool
    {
        $project = Project::find($projectId);

        if ($project === null) {
            return false;
        }

        $previous = $project['counselor_id'] !== null ? (int) $project['counselor_id'] : null;
        Project::update($projectId, ['counselor_id' => $counselorId]);

        Audit::log('projects.counselor_assigned', 'projects', $projectId, [
            'from' => $previous,
            'to' => $counselorId,
        ], ['project_id' => $projectId]);

        Notifier::push(
            (int) $project['client_id'],
            Notifier::TYPE_ALERT,
            sprintf('Un conseiller est affecté à votre dossier %s', $project['reference']),
            $note ?? '',
            $projectId
        );

        // Future fonctionnalité : notifier également le conseiller affecté.
        return true;
    }

    public function updateStatus(int $projectId, string $status, array $user): bool
    {
        if (!in_array($status, ['en_cours', 'bloque', 'termine', 'archive'], true)) {
            return false;
        }

        $project = Project::find($projectId);

        if ($project === null) {
            return false;
        }

        $data = ['status' => $status];

        if ($status === 'termine') {
            $data['closed_at'] = date('Y-m-d H:i:s');
        }

        if ($status === 'en_cours') {
            $data['closed_at'] = null;
        }

        if ($status === 'archive') {
            $data['archived_at'] = date('Y-m-d H:i:s');
        }

        Project::update($projectId, $data);

        Audit::log('projects.status_updated', 'projects', $projectId, [
            'from' => $project['status'],
            'to' => $status,
        ], ['project_id' => $projectId]);

        Notifier::push(
            (int) $project['client_id'],
            Notifier::TYPE_ALERT,
            sprintf('Dossier %s : statut « %s »', $project['reference'], WorkflowService::formatLabel($status)),
            '',
            $projectId
        );

        return true;
    }
}