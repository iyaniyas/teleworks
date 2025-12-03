<?php
return [
    'environment' => env('FLIP_ENV', 'sandbox'),
    'sandbox_base_url' => env('FLIP_SANDBOX_BASE_URL', 'https://bigflip.id/big_sandbox_api'),
    'production_base_url' => env('FLIP_PRODUCTION_BASE_URL', 'https://bigflip.id/api'),
    'secret_key' => env('FLIP_SECRET_KEY', null),
    'payment_callback_secret' => env('FLIP_WEBHOOK_SECRET', null),
    'default_currency' => env('FLIP_CURRENCY', 'IDR'),
    'timeout' => 30,
];

