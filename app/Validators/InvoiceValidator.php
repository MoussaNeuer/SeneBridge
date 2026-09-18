<?php

declare(strict_types=1);

namespace App\Validators;

use App\Support\Validator;

final class InvoiceValidator
{
    public static function register(array $data): Validator
    {
        return Validator::make($data, [
            'client_id' => 'required|int',
            'project_id' => 'nullable|int',
            'amount' => 'required|numeric|min_value:0.01',
            'currency' => 'nullable|in:XOF,USD,EUR',
            'issue_date' => 'required|date',
            'due_date' => 'nullable|date',
            'description' => 'nullable|string|max:1000',
        ]);
    }
}