<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Payment;
use App\Repositories\PaymentRepository;
use App\Support\Audit;
use App\Support\Log;
use App\Support\Str;

final class PaymentService
{
    private PaymentRepository $payments;
    private InvoiceService $invoices;

    public function __construct(?PaymentRepository $payments = null, ?InvoiceService $invoices = null)
    {
        $this->payments = $payments ?? new PaymentRepository();
        $this->invoices = $invoices ?? new InvoiceService();
    }

    /**
     * Enregistre un paiement (statut « en cours ») avec reçu éventuel.
     *
     * @return array{payment: array<string, mixed>|null, error: ?string}
     */
    public function record(array $data, array $file, array $user): array
    {
        $receiptPath = null;

        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE && ($file['tmp_name'] ?? '') !== '') {
            $stored = FileService::store(
                $file,
                FileService::DIR_RECEIPTS,
                (int) config('storage.uploads.max_size', 10485760),
                false,
                (array) config('storage.uploads.allowed_mime_types', [])
            );

            if (!$stored['ok']) {
                return ['payment' => null, 'error' => $stored['error']];
            }

            $receiptPath = $stored['internal_name'];
        }

        $reference = Str::projectReference('PAY', $this->payments->nextReferenceSequence());

        try {
            $payment = Payment::create([
                'reference' => $reference,
                'invoice_id' => $data['invoice_id'] ?? null,
                'project_id' => $data['project_id'] ?? null,
                'client_id' => (int) $data['client_id'],
                'amount' => (float) $data['amount'],
                'currency' => $data['currency'] ?? 'XOF',
                'payment_date' => $data['payment_date'] ?? date('Y-m-d'),
                'method' => $data['method'],
                'notes' => trim((string) ($data['notes'] ?? '')) !== '' ? trim((string) $data['notes']) : null,
                'receipt_internal_name' => $receiptPath,
                'status' => 'en_cours',
                'recorded_by' => (int) $user['id'],
            ]);
        } catch (\Throwable $e) {
            if ($receiptPath !== null) {
                FileService::delete(FileService::DIR_RECEIPTS, $receiptPath);
            }

            Log::error('Enregistrement de paiement impossible', ['error' => $e->getMessage()]);

            return ['payment' => null, 'error' => 'Impossible d\'enregistrer le paiement.'];
        }

        if ($payment === null) {
            return ['payment' => null, 'error' => 'Impossible d\'enregistrer le paiement.'];
        }

        Audit::log('payments.recorded', 'payments', (int) $payment['id'], [], [
            'reference' => $reference,
            'client_id' => (int) $payment['client_id'],
            'amount' => (float) $payment['amount'],
            'method' => $payment['method'],
        ]);

        return ['payment' => $payment, 'error' => null];
    }

    public function validate(int $paymentId, array $user): ?array
    {
        $payment = Payment::find($paymentId);

        if ($payment === null || $payment['status'] !== 'en_cours') {
            return $payment;
        }

        Payment::update($paymentId, [
            'status' => 'valide',
            'validated_at' => date('Y-m-d H:i:s'),
        ]);

        Audit::log('payments.validated', 'payments', $paymentId, [
            'invoice_id' => $payment['invoice_id'] !== null ? (int) $payment['invoice_id'] : null,
        ], ['payment_id' => $paymentId]);

        if ($payment['invoice_id'] !== null) {
            $this->invoices->recompute((int) $payment['invoice_id']);
        }

        Notifier::push(
            (int) $payment['client_id'],
            Notifier::TYPE_PAYMENT,
            sprintf('Paiement %s validé', $payment['reference']),
            sprintf('%s %s', number_format((float) $payment['amount'], 0, ',', ' '), $payment['currency']),
            $payment['project_id'] !== null ? (int) $payment['project_id'] : null
        );

        return Payment::find($paymentId);
    }

    public function reject(int $paymentId, array $user): ?array
    {
        $payment = Payment::find($paymentId);

        if ($payment === null || $payment['status'] !== 'en_cours') {
            return $payment;
        }

        Payment::update($paymentId, ['status' => 'rejete']);

        Audit::log('payments.rejected', 'payments', $paymentId, [], ['payment_id' => $paymentId]);

        Notifier::push(
            (int) $payment['client_id'],
            Notifier::TYPE_PAYMENT,
            sprintf('Paiement %s rejeté', $payment['reference']),
            'Contactez l\'équipe pour vérifier votre règlement.',
            $payment['project_id'] !== null ? (int) $payment['project_id'] : null
        );

        return Payment::find($paymentId);
    }
}