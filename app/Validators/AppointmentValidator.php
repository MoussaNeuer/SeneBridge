<?php

declare(strict_types=1);

namespace App\Validators;

use App\Support\Validator;

final class AppointmentValidator
{
    public static function create(array $data): Validator
    {
        return Validator::make($data, [
            'project_id' => 'nullable|int',
            'counselor_id' => 'required|int',
            'requested_date' => 'required|date',
            'requested_time' => 'required|string|max:5',
            'motive' => 'required|string|max:1000',
        ]);
    }
}