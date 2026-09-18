<?php

declare(strict_types=1);

namespace App\Validators;

use App\Support\Validator;

final class ProfileValidator
{
    public static function update(array $data): Validator
    {
        return Validator::make($data, [
            'first_name' => 'required|string|max:191',
            'last_name' => 'required|string|max:191',
            'phone' => 'nullable|phone',
            'locality' => 'nullable|string|max:191',
            'language' => 'nullable|in:fr,en',
        ]);
    }

    public static function password(array $data): Validator
    {
        return Validator::make($data, [
            'current_password' => 'required',
            'password' => 'required|min:12|confirmed',
        ]);
    }
}