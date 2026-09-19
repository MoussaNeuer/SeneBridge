<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Models\Invoice;
use App\Repositories\InvoiceRepository;
use App\Repositories\PaymentRepository;
use App\Support\Response;

final class InvoiceController extends ApiController
{
    private InvoiceRepository $invoices;
    private PaymentRepository $payments;

    public function __construct()
    {
        parent::__construct();
        $this->invoices = new InvoiceRepository();
        $this->payments = new PaymentRepository();
    }

    /**
     * GET /api/v1/invoices
     */
    public function index(): Response
    {
        if (!$this->isStaff()) {
            $items = $this->invoices->forClient($this->id());

            return $this->ok(['items' => $items, 'total' => count($items), 'per_page' => count($items), 'page' => 1, 'last_page' => 1]);
        }

        $filters = ['status' => (string) $this->request->query('status', '')];
        ['page' => $page, 'per_page' => $perPage] = $this->paging();

        return $this->page($this->invoices->listManaged($filters, $perPage, $page));
    }

    /**
     * GET /api/v1/invoices/{publicId}
     */
    public function show(string $publicId): Response
    {
        $invoice = Invoice::findByPublicId($publicId);

        if ($invoice === null || !$this->owns(isset($invoice['client_id']) ? (int) $invoice['client_id'] : null)) {
            return $this->notFound('Facture introuvable.');
        }

        $detail = $this->invoices->detail((int) $invoice['id']);

        if ($detail === null) {
            return $this->notFound('Facture introuvable.');
        }

        $detail['payments'] = $this->payments->forInvoice((int) $invoice['id']);

        return $this->ok($detail);
    }
}