<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\Controller;
use App\Models\Invoice;
use App\Models\Role;
use App\Repositories\InvoiceRepository;
use App\Repositories\PaymentRepository;
use App\Services\InvoiceService;
use App\Support\App;
use App\Support\Database;
use App\Support\Response;
use App\Validators\InvoiceValidator;

final class InvoiceController extends Controller
{
    private InvoiceRepository $invoices;
    private PaymentRepository $payments;
    private InvoiceService $service;

    public function __construct()
    {
        parent::__construct();
        $this->invoices = new InvoiceRepository();
        $this->payments = new PaymentRepository();
        $this->service = new InvoiceService($this->invoices, $this->payments);
    }

    public function index(): Response
    {
        $user = App::user();
        $filters = ['status' => (string) $this->request->query('status', '')];
        $page = max(1, (int) $this->request->query('page', 1));

        return Response::view('admin/invoices/index', [
            'user' => $user,
            'pagination' => $this->invoices->listManaged($filters, 20, $page),
            'filters' => $filters,
            'stats' => $this->invoices->stats(),
        ]);
    }

    public function create(): Response
    {
        return Response::view('admin/invoices/create', [
            'user' => App::user(),
            'clients' => (new \App\Repositories\UserRepository())->paginateByRole(Role::CLIENT, 500),
            'projects' => $this->lightProjects(),
            'prefillClientId' => $this->prefillClientId(),
        ]);
    }

    public function store(): Response
    {
        $data = $this->request->only([
            'client_id', 'project_id', 'amount', 'currency', 'issue_date', 'due_date', 'description',
        ]);
        $validation = InvoiceValidator::register($data);

        if (!$validation->passes()) {
            return $this->backWithErrors($validation->errors(), $data);
        }

        $client = (new \App\Repositories\UserRepository())->findById((int) $data['client_id']);
        if ($client === null || (int) $client['role_id'] !== (int) (\App\Models\Role::findByName(Role::CLIENT)['id'] ?? 0)) {
            App::flash('error', 'Le client sélectionné est invalide.');
            App::remember($data);

            return Response::redirectBack();
        }

        $result = $this->service->create($data, App::user());

        if ($result['error'] !== null || $result['invoice'] === null) {
            App::flash('error', $result['error']);
            App::remember($data);

            return Response::redirectBack();
        }

        App::flash('success', sprintf('Facture %s créée (brouillon).', $result['invoice']['number']));

        return Response::redirect(route('admin.invoices.show', ['publicId' => $result['invoice']['public_id']]));
    }

    public function show(string $publicId): Response
    {
        $user = App::user();
        $invoice = Invoice::findByPublicId($publicId);

        if ($invoice === null) {
            return Response::notFound('Cette facture est introuvable.');
        }

        $detail = $this->invoices->detail((int) $invoice['id']);

        return Response::view('admin/invoices/show', [
            'user' => $user,
            'invoice' => $detail,
            'payments' => $this->payments->forInvoice((int) $invoice['id']),
        ]);
    }

    public function send(string $publicId): Response
    {
        $invoice = Invoice::findByPublicId($publicId);

        if ($invoice === null) {
            return Response::notFound('Cette facture est introuvable.');
        }

        $updated = $this->service->send((int) $invoice['id'], App::user());
        App::flash($updated !== null && $updated['status'] === 'envoyee' ? 'success' : 'error',
            $updated !== null && $updated['status'] === 'envoyee'
                ? sprintf('Facture %s envoyée au client.', $invoice['number'])
                : 'Impossible d\'envoyer cette facture.');

        return Response::redirect(route('admin.invoices.show', ['publicId' => $invoice['public_id']]));
    }

    public function cancel(string $publicId): Response
    {
        $invoice = Invoice::findByPublicId($publicId);

        if ($invoice === null) {
            return Response::notFound('Cette facture est introuvable.');
        }

        $ok = $this->service->cancel((int) $invoice['id'], App::user());
        App::flash($ok ? 'success' : 'error', $ok ? 'Facture annulée.' : 'Cette facture ne peut pas être annulée.');

        return Response::redirect(route('admin.invoices.show', ['publicId' => $invoice['public_id']]));
    }

    /**
     * Options de dossier pour les formulaires de facturation.
     *
     * @return array<int, array<string, mixed>>
     */
    private function lightProjects(): array
    {
        return Database::select(
            'SELECT p.id, p.reference, p.name, p.client_id
             FROM projects p
             ORDER BY p.created_at DESC
             LIMIT 200'
        );
    }

    private function prefillClientId(): ?int
    {
        $clientPublicId = (string) $this->request->query('client', '');

        if ($clientPublicId === '') {
            return null;
        }

        $client = (new \App\Repositories\UserRepository())->findByPublicId($clientPublicId);

        return $client !== null ? (int) $client['id'] : null;
    }
}