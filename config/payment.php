<?php

declare(strict_types=1);

return [
    'wave' => [
        'business_link' => env('WAVE_BUSINESS_LINK', ''),
        'enabled' => (bool) env('WAVE_ENABLED', false),
    ],

    'currencies' => ['XOF', 'EUR', 'USD'],

    'invoice' => [
        'number_prefix' => env('INVOICE_NUMBER_PREFIX', 'SB-'),
        'number_length' => 6,
    ],
];