<?php

declare(strict_types=1);

return [
    'private_path' => storage_path('private'),
    'public_path' => asset_path('assets'),

    'uploads' => [
        'max_size' => (int) env('UPLOAD_MAX_SIZE', 10485760),
        'allowed_mime_types' => [
            'image/jpeg',
            'image/png',
            'image/gif',
            'image/webp',
            'application/pdf',
        ],
        'allowed_extensions' => [
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'webp' => 'image/webp',
            'pdf' => 'application/pdf',
        ],
    ],

    'media' => [
        'max_size' => (int) env('MEDIA_MAX_SIZE', 52428800),
        'thumbnail_width' => 480,
    ],
];