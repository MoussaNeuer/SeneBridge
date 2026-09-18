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
            'doc' => 'application/msword',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'xls' => 'application/vnd.ms-excel',
            'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'ppt' => 'application/vnd.ms-powerpoint',
            'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
            'odt' => 'application/vnd.oasis.opendocument.text',
            'ods' => 'application/vnd.oasis.opendocument.spreadsheet',
            'txt' => 'text/plain',
            'csv' => 'text/csv',
            'zip' => 'application/zip',
        ],
    ],

    'media' => [
        'max_size' => (int) env('MEDIA_MAX_SIZE', 52428800),
        'thumbnail_width' => 480,
    ],
];