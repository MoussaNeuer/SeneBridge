<?php

declare(strict_types=1);

return [
    'password' => [
        'algorithm' => PASSWORD_ARGON2ID,
        'memory_cost' => (int) env('PASSWORD_MEMORY_COST', 65536),
        'time_cost' => (int) env('PASSWORD_TIME_COST', 4),
        'threads' => (int) env('PASSWORD_THREADS', 1),
    ],
    'session' => [
        'name' => env('SESSION_NAME', 'senebridge_session'),
        'lifetime_minutes' => (int) env('SESSION_LIFETIME', 120),
        'cookie_httponly' => true,
        'cookie_secure' => (bool) env('SESSION_SECURE', false),
        'cookie_samesite' => env('SESSION_SAMESITE', 'Lax'),
        'gc_probability' => 1,
        'gc_divisor' => 100,
    ],
    'csrf' => [
        'token_name' => '_token',
        'header_name' => 'X-CSRF-Token',
        'token_length_bytes' => 32,
    ],
    'rate_limit' => [
        'login' => ['max_attempts' => 5, 'decay_minutes' => 15],
        'register' => ['max_attempts' => 10, 'decay_minutes' => 60],
        'password_reset_request' => ['max_attempts' => 5, 'decay_minutes' => 60],
    ],
    'app_key_min_length' => 32,
];