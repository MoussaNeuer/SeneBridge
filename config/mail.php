<?php

declare(strict_types=1);

return [
    'default' => env('MAIL_MAILER', 'log'),

    'mailers' => [
        'log' => [
            'path' => storage_path('logs/mail.log'),
        ],
'smtp' => [
            'host' => env('MAIL_HOST', 'smtp.mailgun.org'),
            'port' => (int) env('MAIL_PORT', 587),
            'username' => env('MAIL_USERNAME', ''),
            'password' => env('MAIL_PASSWORD', ''),
            'encryption' => env('MAIL_ENCRYPTION', 'tls'),
            'auth' => (bool) env('MAIL_AUTH', true),
            'timeout' => (int) env('MAIL_TIMEOUT', 15),
        ],
    ],

    'from' => [
        'address' => env('MAIL_FROM_ADDRESS', 'no-reply@senebridge.sn'),
        'name' => env('MAIL_FROM_NAME', 'SeneBridge'),
    ],

    'verification' => [
        'expires_minutes' => (int) env('VERIFICATION_EMAIL_EXPIRY', 120),
    ],

    'password_reset' => [
        'expires_minutes' => (int) env('PASSWORD_RESET_EXPIRY', 60),
        'reset_url' => env('APP_URL', 'http://localhost/SeneBridge/public') . '/reset-password',
    ],
];