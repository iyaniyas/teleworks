<?php
return [
    'environment' => env('MIDTRANS_ENV', 'sandbox'),
    'sandbox_base_url' => env('MIDTRANS_SANDBOX_BASE_URL', 'https://app.sandbox.midtrans.com'),
    'production_base_url' => env('MIDTRANS_PRODUCTION_BASE_URL', 'https://app.midtrans.com'),
    'server_key' => env('MIDTRANS_SERVER_KEY'),
    'client_key' => env('MIDTRANS_CLIENT_KEY'),
    'currency' => env('MIDTRANS_CURRENCY', 'IDR'),
    'timeout' => env('MIDTRANS_TIMEOUT', 30),
    'snap_endpoint' => env('MIDTRANS_SNAP_ENDPOINT', '/snap/v1/transactions'),
];

