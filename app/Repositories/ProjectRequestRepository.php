<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\ProjectRequest;
use App\Support\Database;

final class ProjectRequestRepository
{
    public function paginated(int $perPage = 20, int $page = 1, array $filters = []): array
    {
        $conditions = [];

        if (isset($filters['status']) && $filters['status'] !== '') {
            $conditions['status'] = $filters['status'];
        }

        return ProjectRequest::paginate($conditions, $perPage, $page, [['created_at', 'DESC']]);
    }

    /**
     * @return array<string, int>
     */
    public function statusCounts(): array
    {
        $rows = Database::select(
            'SELECT status, COUNT(*) AS total FROM project_requests GROUP BY status'
        );

        $counts = array_fill_keys(ProjectRequest::STATUSES, 0);
        foreach ($rows as $row) {
            $counts[$row['status']] = (int) $row['total'];
        }

        return $counts;
    }

    public function countNew(): int
    {
        return ProjectRequest::count(['status' => 'nouveau']);
    }
}