<?php

declare(strict_types=1);

namespace App\Validators;

use App\Support\Validator;

final class ContactValidator
{
    public static function register(array $data): Validator
    {
        return Validator::make($data, [
            'name' => 'required|string|max:191',
            'email' => 'required|email|max:191',
            'phone' => 'nullable|phone',
            'subject' => 'required|string|max:191',
            'message' => 'required|string|max:5000',
        ]);
    }
}