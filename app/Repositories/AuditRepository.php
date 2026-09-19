<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Support\Database;

final class AuditRepository
{
    /**
     * Journal d'audit paginé, avec recherche et filtre par action.
     *
     * @return array<string, mixed>
     */
    public function paginated(array $filters = [], int $perPage = 25, int $page = 1): array
    {
        $where = [];
        $params = [];

        if (isset($filters['action']) && $filters['action'] !== '') {
            $where[] = 'a.action = ?';
            $params[] = (string) $filters['action'];
        }

        $q = isset($filters['q']) ? trim((string) $filters['q']) : '';
        if ($q !== '') {
            $where[] = '(a.action LIKE ? OR a.entity_type LIKE ? OR u.first_name LIKE ? OR u.last_name LIKE ?)';
            $like = '%' . $q . '%';
            array_push($params, $like, $like, $like, $like);
        }

        $whereSql = $where !== [] ? 'WHERE ' . implode(' AND ', $where) : '';
        $total = (int) Database::scalar(
            'SELECT COUNT(*) FROM audit_logs a LEFT JOIN users u ON u.id = a.user_id ' . $whereSql,
            $params
        );

        $perPage = max(1, min(100, $perPage));
        $offset = ($page - 1) * $perPage;

        $rows = Database::select(
            'SELECT a.*,
                    u.first_name, u.last_name, u.email
             FROM audit_logs a
             LEFT JOIN users u ON u.id = a.user_id
             ' . $whereSql . '
             ORDER BY a.id DESC
             LIMIT ' . $perPage . ' OFFSET ' . $offset,
            $params
        );

        return [
            'items' => $rows,
            'total' => $total,
            'per_page' => $perPage,
            'page' => $page,
            'last_page' => (int) ceil($total / $perPage),
        ];
    }

    /**
     * Actions distinctes pour le filtre du journal.
     *
     * @return array<int, string>
     */
    public function actions(): array
    {
        $rows = Database::select('SELECT DISTINCT action FROM audit_logs ORDER BY action ASC');

        return array_map(static fn (array $r): string => (string) $r['action'], $rows);
    }
}