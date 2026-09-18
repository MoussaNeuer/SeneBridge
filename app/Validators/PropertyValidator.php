<?php

declare(strict_types=1);

namespace App\Validators;

use App\Support\Validator;

final class PropertyValidator
{
    public static function register(array $data): Validator
    {
        return Validator::make($data, [
            'project_id' => 'nullable|int',
            'owner_client_id' => 'nullable|int',
            'type' => 'required|in:terrain,appartement,maison,villa,local_commercial,autre',
            'name' => 'required|string|max:191',
            'description' => 'nullable|string',
            'locality' => 'nullable|string|max:191',
            'price' => 'nullable|numeric|min_value:0',
            'currency' => 'nullable|in:XOF,USD,EUR',
            'status' => 'nullable|in:disponible,sous_offre,reserve,vendu,loue,archive',
        ]);
    }
}