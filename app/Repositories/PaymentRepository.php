<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Payment;
use App\Support\Database;

final class PaymentRepository
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

        return Payment::paginate($conditions, $perPage, $page, [['payment_date', 'DESC'], ['id', 'DESC']]);
    }

    /**
     * @return array<string, mixed>|null
     */
    public function detail(int $id): ?array
    {
        return Database::first(
            'SELECT p.*,
                    c.first_name AS client_first_name, c.last_name AS client_last_name,
                    c.email AS client_email,
                    i.number AS invoice_number, i.amount AS invoice_amount, i.status AS invoice_status,
                    pr.reference AS project_reference, pr.name AS project_name,
                    rb.first_name AS recorded_first_name, rb.last_name AS recorded_last_name
             FROM payments p
             LEFT JOIN users c ON c.id = p.client_id
             LEFT JOIN invoices i ON i.id = p.invoice_id
             LEFT JOIN projects pr ON pr.id = p.project_id
             LEFT JOIN users rb ON rb.id = p.recorded_by
             WHERE p.id = ? LIMIT 1',
            [$id]
        );
    }

    /**
     * Paiements d'un client.
     *
     * @return array<int, array<string, mixed>>
     */
    public function forClient(int $clientId): array
    {
        return Payment::where(['client_id' => $clientId], [['payment_date', 'DESC'], ['id', 'DESC']]);
    }

    /**
     * Paiements d'une facture.
     *
     * @return array<int, array<string, mixed>>
     */
    public function forInvoice(int $invoiceId): array
    {
        return Payment::where(['invoice_id' => $invoiceId], [['payment_date', 'DESC'], ['id', 'DESC']]);
    }

    /**
     * Séquence suivante pour la référence de paiement.
     */
    public function nextReferenceSequence(): int
    {
        return (int) Database::scalar('SELECT COUNT(*) FROM payments') + 1;
    }

    /**
     * @return array<string, mixed>
     */
    public function stats(): array
    {
        return [
            'en_cours' => Payment::count(['status' => 'en_cours']),
            'valide' => Payment::count(['status' => 'valide']),
            'rejete' => Payment::count(['status' => 'rejete']),
            'rembourse' => Payment::count(['status' => 'rembourse']),
            'montant_valide' => (float) Database::scalar(
                "SELECT COALESCE(SUM(amount), 0) FROM payments WHERE status = 'valide'"
            ),
        ];
    }
}