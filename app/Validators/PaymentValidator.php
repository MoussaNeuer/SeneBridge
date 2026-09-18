<?php

declare(strict_types=1);

namespace App\Validators;

use App\Support\Validator;

final class PaymentValidator
{
    public static function record(array $data): Validator
    {
        return Validator::make($data, [
            'invoice_id' => 'nullable|int',
            'client_id' => 'required|int',
            'amount' => 'required|numeric|min_value:0.01',
            'currency' => 'nullable|in:XOF,USD,EUR',
            'payment_date' => 'required|date',
            'method' => 'required|in:wave_business,bank_transfer,cash,other',
            'reference' => 'nullable|string|max:191',
            'notes' => 'nullable|string|max:1000',
        ]);
    }
}