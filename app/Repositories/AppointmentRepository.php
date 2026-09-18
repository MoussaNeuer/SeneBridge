<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Appointment;
use App\Support\Database;

final class AppointmentRepository
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public function forClient(int $clientId): array
    {
        $rows = Database::select(
            'SELECT a.*,
                    co.first_name AS counselor_first_name, co.last_name AS counselor_last_name,
                    p.reference AS project_reference, p.name AS project_name
             FROM appointments a
             LEFT JOIN users co ON co.id = a.counselor_id
             LEFT JOIN projects p ON p.id = a.project_id
             WHERE a.client_id = ?
             ORDER BY a.requested_date DESC, a.requested_time DESC',
            [$clientId]
        );

        return $rows;
    }

    /**
     * Liste back-office, scopée : un conseiller ne voit que ses rendez-vous.
     *
     * @return array<string, mixed>
     */
    public function listManaged(?array $user, array $filters = [], int $perPage = 20, int $page = 1): array
    {
        $where = [];
        $params = [];

        if ($user !== null && (int) ($user['role_id'] ?? 0) > 0) {
            $roleName = \App\Support\Gate::roleName($user);

            if ($roleName === 'counselor') {
                $where[] = 'a.counselor_id = ?';
                $params[] = (int) $user['id'];
            }
        }

        if (isset($filters['status']) && $filters['status'] !== '') {
            $where[] = 'a.status = ?';
            $params[] = (string) $filters['status'];
        }

        $whereSql = $where !== [] ? 'WHERE ' . implode(' AND ', $where) : '';
        $total = (int) Database::scalar(
            'SELECT COUNT(*) FROM appointments a ' . $whereSql,
            $params
        );

        $perPage = max(1, min(100, $perPage));
        $offset = ($page - 1) * $perPage;

        $rows = Database::select(
            'SELECT a.*,
                    cl.first_name AS client_first_name, cl.last_name AS client_last_name,
                    cl.email AS client_email, cl.phone AS client_phone,
                    co.first_name AS counselor_first_name, co.last_name AS counselor_last_name,
                    p.reference AS project_reference, p.name AS project_name
             FROM appointments a
             LEFT JOIN users cl ON cl.id = a.client_id
             LEFT JOIN users co ON co.id = a.counselor_id
             LEFT JOIN projects p ON p.id = a.project_id
             ' . $whereSql . '
             ORDER BY a.requested_date DESC, a.requested_time DESC
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
     * @return array<string, mixed>|null
     */
    public function detail(int $id): ?array
    {
        return Database::first(
            'SELECT a.*,
                    cl.first_name AS client_first_name, cl.last_name AS client_last_name,
                    cl.email AS client_email, cl.phone AS client_phone,
                    co.first_name AS counselor_first_name, co.last_name AS counselor_last_name,
                    co.email AS counselor_email,
                    p.reference AS project_reference, p.name AS project_name
             FROM appointments a
             LEFT JOIN users cl ON cl.id = a.client_id
             LEFT JOIN users co ON co.id = a.counselor_id
             LEFT JOIN projects p ON p.id = a.project_id
             WHERE a.id = ? LIMIT 1',
            [$id]
        );
    }

    public function find(int $id): ?array
    {
        return Appointment::find($id);
    }

    public function findByPublicId(string $publicId): ?array
    {
        return Appointment::findByPublicId($publicId);
    }

    /**
     * @return array<string, mixed>
     */
    public function stats(): array
    {
        return [
            'demande' => Appointment::count(['status' => 'demande']),
            'confirme' => Appointment::count(['status' => 'confirme']),
            'annule' => Appointment::count(['status' => 'annule']),
            'termine' => Appointment::count(['status' => 'termine']),
            'aujourdhui' => Appointment::count(['requested_date' => date('Y-m-d'), 'status' => 'confirme']),
        ];
    }
}