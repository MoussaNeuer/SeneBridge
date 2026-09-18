<?php

declare(strict_types=1);

namespace App\Validators;

use App\Support\Validator;

final class ProjectRequestValidator
{
    public static function register(array $data): Validator
    {
        return Validator::make($data, [
            'intent' => 'required|in:je_cherche_un_bien,jai_un_projet,accompagnement',
            'project_type' => 'required|in:immobilier,gestion_projets,import_export,auto,conciergerie,investissement',
            'first_name' => 'required|string|max:191',
            'last_name' => 'required|string|max:191',
            'email' => 'required|email|max:191',
            'phone' => 'nullable|phone',
            'locality' => 'nullable|string|max:191',
            'budget' => 'nullable|numeric|min_value:0',
            'description' => 'nullable|string|max:5000',
        ]);
    }

    public static function updateStatus(array $data): Validator
    {
        return Validator::make($data, [
            'status' => 'required|in:nouveau,qualification,converti,archive',
        ]);
    }
}