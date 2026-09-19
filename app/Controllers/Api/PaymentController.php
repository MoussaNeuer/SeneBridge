<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Models\Payment;
use App\Repositories\PaymentRepository;
use App\Support\Response;

final class PaymentController extends ApiController
{
    private PaymentRepository $payments;

    public function __construct()
    {
        parent::__construct();
        $this->payments = new PaymentRepository();
    }

    /**
     * GET /api/v1/payments
     */
    public function index(): Response
    {
        if (!$this->isStaff()) {
            $items = $this->payments->forClient($this->id());

            return $this->ok(['items' => $items, 'total' => count($items), 'per_page' => count($items), 'page' => 1, 'last_page' => 1]);
        }

        $filters = ['status' => (string) $this->request->query('status', '')];
        ['page' => $page, 'per_page' => $perPage] = $this->paging();

        return $this->page($this->payments->listManaged($filters, $perPage, $page));
    }

    /**
     * GET /api/v1/payments/{publicId}
     */
    public function show(string $publicId): Response
    {
        $payment = Payment::findByPublicId($publicId);

        if ($payment === null || !$this->owns(isset($payment['client_id']) ? (int) $payment['client_id'] : null)) {
            return $this->notFound('Paiement introuvable.');
        }

        $detail = $this->payments->detail((int) $payment['id']);

        if ($detail === null) {
            return $this->notFound('Paiement introuvable.');
        }

        return $this->ok($detail);
    }
}