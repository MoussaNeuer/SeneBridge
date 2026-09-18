<?php

declare(strict_types=1);

namespace App\Validators;

use App\Support\Validator;

final class ArticleValidator
{
    public static function register(array $data): Validator
    {
        return Validator::make($data, [
            'title' => 'required|string|max:191',
            'excerpt' => 'nullable|string|max:500',
            'body' => 'required|string',
            'cover_url' => 'nullable|string|max:500',
            'status' => 'nullable|in:brouillon,publie',
        ]);
    }
}