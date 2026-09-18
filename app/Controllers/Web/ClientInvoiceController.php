<?php

declare(strict_types=1);

namespace App\Controllers\Web;

use App\Controllers\Controller;
use App\Models\Invoice;
use App\Repositories\InvoiceRepository;
use App\Repositories\PaymentRepository;
use App\Support\App;
use App\Support\Response;

final class ClientInvoiceController extends Controller
{
    private InvoiceRepository $invoices;
    private PaymentRepository $payments;

    public function __construct()
    {
        parent::__construct();
        $this->invoices = new InvoiceRepository();
        $this->payments = new PaymentRepository();
    }

    public function index(): Response
    {
        $user = App::user();

        return Response::view('client/invoices/index', [
            'invoices' => $this->invoices->forClient((int) $user['id']),
            'unpaidCount' => $this->invoices->countUnpaidForClient((int) $user['id']),
        ]);
    }

    public function show(string $publicId): Response
    {
        $user = App::user();
        $invoice = Invoice::findByPublicId($publicId);

        // IDOR : le client ne voit que ses propres factures.
        if ($invoice === null || (int) $invoice['client_id'] !== (int) $user['id']) {
            return Response::notFound('Cette facture est introuvable.');
        }

        $detail = $this->invoices->detail((int) $invoice['id']);

        return Response::view('client/invoices/show', [
            'invoice' => $detail,
            'payments' => $this->payments->forInvoice((int) $invoice['id']),
            'canPay' => in_array($invoice['status'], ['envoyee', 'partielle', 'en_retard'], true),
        ]);
    }
}