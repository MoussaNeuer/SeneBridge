<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Invoice;
use App\Support\Database;

final class InvoiceRepository
{
    /**
     * @return array<string, mixed>
     */
    public function listManaged(array $filters = [], int $perPage = 20, int $page = 1): array
    {
        $conditions = [];

        if (isset($filters['status']) && $filters['status'] !== '') {
            $conditions['status'] = $filters['status'];
        }

        return Invoice::paginate($conditions, $perPage, $page, [['created_at', 'DESC']]);
    }

    /**
     * Facture avec le client et le dossier joints.
     *
     * @return array<string, mixed>|null
     */
    public function detail(int $id): ?array
    {
        return Database::first(
            'SELECT i.*,
                    c.first_name AS client_first_name, c.last_name AS client_last_name,
                    c.email AS client_email, c.phone AS client_phone,
                    p.reference AS project_reference, p.name AS project_name
             FROM invoices i
             LEFT JOIN users c ON c.id = i.client_id
             LEFT JOIN projects p ON p.id = i.project_id
             WHERE i.id = ? LIMIT 1',
            [$id]
        );
    }

    /**
     * Factures d'un client (hors factures supprimées).
     *
     * @return array<int, array<string, mixed>>
     */
    public function forClient(int $clientId): array
    {
        return Invoice::where(['client_id' => $clientId], [['created_at', 'DESC']]);
    }

    /**
     * Factures rattachées à un dossier.
     *
     * @return array<int, array<string, mixed>>
     */
    public function forProject(int $projectId): array
    {
        return Invoice::where(['project_id' => $projectId], [['created_at', 'DESC']]);
    }

    public function countUnpaidForClient(int $clientId): int
    {
        return Invoice::count(['client_id' => $clientId, 'status' => ['envoyee', 'partielle', 'en_retard']]);
    }

    /**
     * Séquence suivante pour la référence de facture.
     */
    public function nextNumberSequence(): int
    {
        return (int) Database::scalar('SELECT COUNT(*) FROM invoices') + 1;
    }

    /**
     * @return array<string, mixed>
     */
    public function stats(): array
    {
        return [
            'brouillon' => Invoice::count(['status' => 'brouillon']),
            'envoyee' => Invoice::count(['status' => 'envoyee']),
            'partielle' => Invoice::count(['status' => 'partielle']),
            'payee' => Invoice::count(['status' => 'payee']),
            'en_retard' => Invoice::count(['status' => 'en_retard']),
            'annulee' => Invoice::count(['status' => 'annulee']),
            'a_recouvrer' => Invoice::count(['status' => ['envoyee', 'partielle', 'en_retard']]),
        ];
    }
}