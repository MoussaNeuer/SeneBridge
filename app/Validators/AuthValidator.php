<?php

declare(strict_types=1);

namespace App\Validators;

use App\Support\Validator;

final class AuthValidator
{
    /**
     * @param array<string, mixed> $data
     */
    public static function register(array $data): Validator
    {
        return Validator::make($data, [
            'first_name' => 'required|string|min:2|max:60',
            'last_name' => 'required|string|min:2|max:60',
            'email' => 'required|email|max:191',
            'phone' => 'nullable|phone|max:30',
            'password' => 'required|string|min:8|max:72|confirmed',
        ]);
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function login(array $data): Validator
    {
        return Validator::make($data, [
            'email' => 'required|email|max:191',
            'password' => 'required|string|max:72',
        ]);
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function forgot(array $data): Validator
    {
        return Validator::make($data, [
            'email' => 'required|email|max:191',
        ]);
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function reset(array $data): Validator
    {
        return Validator::make($data, [
            'email' => 'required|email|max:191',
            'token' => 'required|string|max:191',
            'password' => 'required|string|min:8|max:72|confirmed',
        ]);
    }
}