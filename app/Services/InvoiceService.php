<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Invoice;
use App\Repositories\InvoiceRepository;
use App\Repositories\PaymentRepository;
use App\Support\Audit;
use App\Support\Database;
use App\Support\Log;
use App\Support\Str;

final class InvoiceService
{
    private InvoiceRepository $invoices;
    private PaymentRepository $payments;

    public function __construct(?InvoiceRepository $invoices = null, ?PaymentRepository $payments = null)
    {
        $this->invoices = $invoices ?? new InvoiceRepository();
        $this->payments = $payments ?? new PaymentRepository();
    }

    /**
     * @return array{invoice: array<string, mixed>|null, error: ?string}
     */
    public function create(array $data, array $user): array
    {
        $sequence = $this->invoices->nextNumberSequence();
        $number = Str::projectReference('FAC', $sequence);

        try {
            $invoice = Invoice::create([
                'number' => $number,
                'client_id' => (int) $data['client_id'],
                'project_id' => $data['project_id'] ?? null,
                'amount' => (float) $data['amount'],
                'paid_amount' => 0,
                'currency' => $data['currency'] ?? 'XOF',
                'issue_date' => $data['issue_date'] ?? date('Y-m-d'),
                'due_date' => ($data['due_date'] ?? '') !== '' ? $data['due_date'] : null,
                'status' => 'brouillon',
                'description' => trim((string) ($data['description'] ?? '')) !== '' ? trim((string) $data['description']) : null,
                'created_by' => (int) $user['id'],
            ]);
        } catch (\Throwable $e) {
            Log::error('Création de facture impossible', ['error' => $e->getMessage()]);

            return ['invoice' => null, 'error' => 'Impossible de créer la facture.'];
        }

        if ($invoice === null) {
            return ['invoice' => null, 'error' => 'Impossible de créer la facture.'];
        }

        Audit::log('invoices.created', 'invoices', (int) $invoice['id'], [], [
            'number' => $number,
            'client_id' => (int) $invoice['client_id'],
            'amount' => (float) $invoice['amount'],
        ]);

        return ['invoice' => $invoice, 'error' => null];
    }

    public function send(int $invoiceId, array $user): ?array
    {
        $invoice = Invoice::find($invoiceId);

        if ($invoice === null || in_array($invoice['status'], ['payee', 'annulee'], true)) {
            return $invoice;
        }

        Invoice::update($invoiceId, ['status' => 'envoyee']);

        Audit::log('invoices.sent', 'invoices', $invoiceId, [
            'from' => $invoice['status'],
            'to' => 'envoyee',
        ], ['invoice_id' => $invoiceId]);

        Notifier::push(
            (int) $invoice['client_id'],
            Notifier::TYPE_INVOICE,
            sprintf('Votre facture %s est disponible', $invoice['number']),
            sprintf('Montant : %s %s', number_format((float) $invoice['amount'], 0, ',', ' '), $invoice['currency']),
            $invoice['project_id'] !== null ? (int) $invoice['project_id'] : null
        );

        return Invoice::find($invoiceId);
    }

    public function cancel(int $invoiceId, array $user): bool
    {
        $invoice = Invoice::find($invoiceId);

        if ($invoice === null || $invoice['status'] === 'payee') {
            return false;
        }

        Invoice::update($invoiceId, ['status' => 'annulee']);

        Audit::log('invoices.cancelled', 'invoices', $invoiceId, [
            'from' => $invoice['status'],
            'to' => 'annulee',
        ], ['invoice_id' => $invoiceId]);

        Notifier::push(
            (int) $invoice['client_id'],
            Notifier::TYPE_INVOICE,
            sprintf('La facture %s a été annulée', $invoice['number']),
            '',
            $invoice['project_id'] !== null ? (int) $invoice['project_id'] : null
        );

        return true;
    }

    /**
     * Statut métier déduit du montant payé (fonction pure, testable).
     *
     * @param string|int|float $paid
     * @param string|int|float $amount
     */
    public static function statusFor($paid, $amount, string $fallback = 'envoyee'): string
    {
        $paid = (float) $paid;
        $amount = (float) $amount;

        if ($paid <= 0) {
            return $fallback;
        }

        if ($paid >= $amount) {
            return 'payee';
        }

        return 'partielle';
    }

    /**
     * Recalcule montant réglé et statut de la facture après validation d'un paiement.
     *
     * @return array<string, mixed>|null
     */
    public function recompute(int $invoiceId): ?array
    {
        $invoice = Invoice::find($invoiceId);

        if ($invoice === null || $invoice['status'] === 'annulee') {
            return $invoice;
        }

        $validated = $this->payments->forInvoice($invoiceId);
        $total = 0.0;

        foreach ($validated as $payment) {
            if ($payment['status'] === 'valide') {
                $total += (float) $payment['amount'];
            }
        }

        $fallback = $invoice['status'] === 'brouillon' ? 'brouillon' : 'envoyee';
        $status = self::statusFor($total, (float) $invoice['amount'], $fallback);

        Invoice::update($invoiceId, [
            'paid_amount' => $total,
            'status' => $status,
            'paid_at' => $status === 'payee' ? date('Y-m-d H:i:s') : null,
        ]);

        return Invoice::find($invoiceId);
    }
}