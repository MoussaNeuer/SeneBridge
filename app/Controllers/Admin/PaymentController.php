<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\Controller;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Role;
use App\Repositories\InvoiceRepository;
use App\Repositories\PaymentRepository;
use App\Services\FileService;
use App\Services\PaymentService;
use App\Support\App;
use App\Support\Database;
use App\Support\Response;
use App\Validators\PaymentValidator;

final class PaymentController extends Controller
{
    private PaymentRepository $payments;
    private InvoiceRepository $invoices;
    private PaymentService $service;

    public function __construct()
    {
        parent::__construct();
        $this->payments = new PaymentRepository();
        $this->invoices = new InvoiceRepository();
        $this->service = new PaymentService($this->payments, new \App\Services\InvoiceService($this->invoices, $this->payments));
    }

    public function index(): Response
    {
        $user = App::user();
        $filters = ['status' => (string) $this->request->query('status', '')];
        $page = max(1, (int) $this->request->query('page', 1));

        return Response::view('admin/payments/index', [
            'user' => $user,
            'pagination' => $this->payments->listManaged($filters, 20, $page),
            'filters' => $filters,
            'stats' => $this->payments->stats(),
        ]);
    }

    public function create(): Response
    {
        $prefill = [
            'invoice_id' => null,
            'client_id' => null,
            'amount' => '',
        ];
        $invoicePublicId = (string) $this->request->query('invoice', '');

        if ($invoicePublicId !== '') {
            $invoice = Invoice::findByPublicId($invoicePublicId);
            if ($invoice !== null) {
                $prefill = [
                    'invoice_id' => (int) $invoice['id'],
                    'client_id' => (int) $invoice['client_id'],
                    'amount' => (string) $invoice['amount'],
                ];
            }
        }

        return Response::view('admin/payments/create', [
            'user' => App::user(),
            'invoices' => $this->payableInvoices(),
            'clients' => (new \App\Repositories\UserRepository())->paginateByRole(Role::CLIENT, 500),
            'prefill' => $prefill,
        ]);
    }

    public function store(): Response
    {
        $data = $this->request->only([
            'invoice_id', 'client_id', 'amount', 'currency', 'payment_date', 'method', 'reference', 'notes',
        ]);
        $validation = PaymentValidator::record($data);

        if (!$validation->passes()) {
            return $this->backWithErrors($validation->errors(), $data);
        }

        $client = (new \App\Repositories\UserRepository())->findById((int) $data['client_id']);
        if ($client === null || (int) $client['role_id'] !== (int) (\App\Models\Role::findByName(Role::CLIENT)['id'] ?? 0)) {
            App::flash('error', 'Le client sélectionné est invalide.');
            App::remember($data);

            return Response::redirectBack();
        }

        // Cohérence : si une facture est saisie, le client doit être le sien.
        $invoiceId = $data['invoice_id'] !== null && $data['invoice_id'] !== '' ? (int) $data['invoice_id'] : null;
        if ($invoiceId !== null) {
            $invoice = Invoice::find($invoiceId);
            if ($invoice === null || (int) $invoice['client_id'] !== (int) $data['client_id']) {
                App::flash('error', 'Cette facture ne correspond pas au client sélectionné.');
                App::remember($data);

                return Response::redirectBack();
            }
        }

        $projectId = $invoiceId !== null ? (int) ($invoice['project_id'] ?? 0) : null;
        $data['project_id'] = $projectId > 0 ? $projectId : null;

        $result = $this->service->record($data, $this->request->file('receipt') ?? [], App::user());

        if ($result['error'] !== null || $result['payment'] === null) {
            App::flash('error', $result['error']);
            App::remember($data);

            return Response::redirectBack();
        }

        App::flash('success', sprintf('Paiement %s enregistré (en attente de validation).', $result['payment']['reference']));

        return Response::redirect(route('admin.payments.show', ['publicId' => $result['payment']['public_id']]));
    }

    public function show(string $publicId): Response
    {
        $payment = Payment::findByPublicId($publicId);

        if ($payment === null) {
            return Response::notFound('Ce paiement est introuvable.');
        }

        return Response::view('admin/payments/show', [
            'user' => App::user(),
            'payment' => $this->payments->detail((int) $payment['id']),
        ]);
    }

    public function validate(string $publicId): Response
    {
        $payment = Payment::findByPublicId($publicId);

        if ($payment === null) {
            return Response::notFound('Ce paiement est introuvable.');
        }

        $updated = $this->service->validate((int) $payment['id'], App::user());
        App::flash($updated !== null && $updated['status'] === 'valide' ? 'success' : 'error',
            $updated !== null && $updated['status'] === 'valide'
                ? sprintf('Paiement %s validé.', $payment['reference'])
                : 'Impossible de valider ce paiement.');

        return Response::redirect(route('admin.payments.show', ['publicId' => $payment['public_id']]));
    }

    public function reject(string $publicId): Response
    {
        $payment = Payment::findByPublicId($publicId);

        if ($payment === null) {
            return Response::notFound('Ce paiement est introuvable.');
        }

        $updated = $this->service->reject((int) $payment['id'], App::user());
        App::flash($updated !== null && $updated['status'] === 'rejete' ? 'success' : 'error',
            $updated !== null && $updated['status'] === 'rejete'
                ? sprintf('Paiement %s rejeté.', $payment['reference'])
                : 'Impossible de rejeter ce paiement.');

        return Response::redirect(route('admin.payments.show', ['publicId' => $payment['public_id']]));
    }

    public function receipt(string $publicId): Response
    {
        $payment = Payment::findByPublicId($publicId);

        if ($payment === null || $payment['receipt_internal_name'] === null || $payment['receipt_internal_name'] === '') {
            return Response::notFound('Aucun reçu attaché à ce paiement.');
        }

        return FileService::download(
            FileService::DIR_RECEIPTS,
            (string) $payment['receipt_internal_name'],
            \App\Services\FileService::mimeOf((string) $payment['receipt_internal_name']),
            sprintf('%s-recu.%s', (string) $payment['reference'], pathinfo((string) $payment['receipt_internal_name'], PATHINFO_EXTENSION))
        );
    }

    /**
     * Factures pour lesquelles un règlement a du sens.
     *
     * @return array<int, array<string, mixed>>
     */
    private function payableInvoices(): array
    {
        return Database::select(
            "SELECT i.id, i.public_id, i.number, i.amount, i.currency,
                    c.first_name, c.last_name, c.id AS client_id
             FROM invoices i
             JOIN users c ON c.id = i.client_id
             WHERE i.status IN ('envoyee', 'partielle', 'en_retard')
             ORDER BY i.created_at DESC
             LIMIT 200"
        );
    }
}