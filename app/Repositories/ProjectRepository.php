<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Project;
use App\Models\ProjectStep;
use App\Support\Database;

final class ProjectRepository
{
    /**
     * Projets appartenant à un client (hors archivés par défaut).
     *
     * @return array<int, array<string, mixed>>
     */
    public function listForClient(int $clientId, bool $includeArchived = false): array
    {
        $conditions = ['client_id' => $clientId];

        if (!$includeArchived) {
            $conditions['status'] = ['en_cours', 'bloque', 'termine'];
        }

        return Project::where($conditions, [['created_at', 'DESC']]);
    }

    public function countForClient(int $clientId): int
    {
        return Project::count(['client_id' => $clientId]);
    }

    public function countActiveForClient(int $clientId): int
    {
        return Project::count(['client_id' => $clientId, 'status' => ['en_cours', 'bloque']]);
    }

    /**
     * Projets attribués à un conseiller.
     *
     * @return array<int, array<string, mixed>>
     */
    public function listForCounselor(int $counselorId): array
    {
        return Project::where(
            ['counselor_id' => $counselorId, 'status' => ['en_cours', 'bloque', 'termine']],
            [['updated_at', 'DESC']]
        );
    }

    public function listManaged(array $filters = [], int $perPage = 20, int $page = 1): array
    {
        $conditions = [];

        if (isset($filters['status']) && $filters['status'] !== '') {
            $conditions['status'] = $filters['status'];
        }

        if (isset($filters['type']) && $filters['type'] !== '') {
            $conditions['type'] = $filters['type'];
        }

        return Project::paginate($conditions, $perPage, $page, [['created_at', 'DESC']]);
    }

    /**
     * Détail avec le client et le conseiller joints.
     *
     * @return array<string, mixed>|null
     */
    public function detail(int $id): ?array
    {
        return Database::first(
            'SELECT p.*,
                    c.first_name AS client_first_name, c.last_name AS client_last_name,
                    c.email AS client_email, c.phone AS client_phone,
                    co.first_name AS counselor_first_name, co.last_name AS counselor_last_name,
                    co.email AS counselor_email,
                    (SELECT COUNT(*) FROM project_steps s WHERE s.project_id = p.id) AS steps_count,
                    (SELECT COUNT(*) FROM project_steps s WHERE s.project_id = p.id AND s.status = \'termine\') AS steps_completed
             FROM projects p
             LEFT JOIN users c ON c.id = p.client_id
             LEFT JOIN users co ON co.id = p.counselor_id
             WHERE p.id = ? LIMIT 1',
            [$id]
        );
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function steps(int $projectId): array
    {
        return ProjectStep::where(['project_id' => $projectId], [['position', 'ASC']]);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function properties(int $projectId): array
    {
        return \App\Models\Property::where(['project_id' => $projectId], [['created_at', 'DESC']]);
    }

    public function countSteps(int $projectId): int
    {
        return ProjectStep::count(['project_id' => $projectId]);
    }

    public function countCompletedSteps(int $projectId): int
    {
        return ProjectStep::count(['project_id' => $projectId, 'status' => 'termine']);
    }

    /**
     * Prochaine étape incomplète d'un projet (ordre du workflow).
     */
    public function currentStep(int $projectId): ?array
    {
        return Database::first(
            'SELECT * FROM project_steps
             WHERE project_id = ? AND status != \'termine\'
             ORDER BY position ASC LIMIT 1',
            [$projectId]
        );
    }

    /**
     * Derniers événements de la timeline d'un projet.
     *
     * @return array<int, array<string, mixed>>
     */
    public function recentActivity(int $projectId, int $limit = 10): array
    {
        return Database::select(
            'SELECT h.*, u.first_name, u.last_name
             FROM project_step_history h
             LEFT JOIN users u ON u.id = h.user_id
             WHERE h.project_step_id IN (SELECT id FROM project_steps WHERE project_id = ?)
             ORDER BY h.created_at DESC
             LIMIT ?',
            [$projectId, $limit]
        );
    }

    /**
     * Dernière référence séquentielle de dossier.
     */
    public function nextReferenceSequence(): int
    {
        $count = (int) Database::scalar('SELECT COUNT(*) FROM projects');

        return $count + 1;
    }

    /**
     * @return array<string, mixed>
     */
    public function stats(): array
    {
        return [
            'total' => Project::count(),
            'en_cours' => Project::count(['status' => 'en_cours']),
            'bloque' => Project::count(['status' => 'bloque']),
            'termine' => Project::count(['status' => 'termine']),
            'archive' => Project::count(['status' => 'archive']),
        ];
    }
}