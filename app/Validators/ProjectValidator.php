<?php

declare(strict_types=1);

namespace App\Validators;

use App\Support\Validator;

final class ProjectValidator
{
    public static function register(array $data): Validator
    {
        return Validator::make($data, [
            'client_id' => 'required|int',
            'name' => 'required|string|max:191',
            'type' => 'required|in:immobilier,gestion_projets,import_export,auto,conciergerie,investissement',
            'locality' => 'nullable|string|max:191',
            'description' => 'nullable|string',
            'budget' => 'nullable|numeric|min_value:0',
            'currency' => 'nullable|in:XOF,USD,EUR',
            'counselor_id' => 'nullable|int',
            'start_date' => 'nullable|date',
            'expected_end_date' => 'nullable|date',
        ]);
    }

    public static function update(array $data): Validator
    {
        return Validator::make($data, [
            'name' => 'required|string|max:191',
            'type' => 'required|in:immobilier,gestion_projets,import_export,auto,conciergerie,investissement',
            'locality' => 'nullable|string|max:191',
            'description' => 'nullable|string',
            'budget' => 'nullable|numeric|min_value:0',
            'currency' => 'nullable|in:XOF,USD,EUR',
            'start_date' => 'nullable|date',
            'expected_end_date' => 'nullable|date',
            'status' => 'nullable|in:en_cours,bloque,termine,archive',
        ]);
    }
}