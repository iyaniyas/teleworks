<?php

return [
    // default feed url (can be overridden in .env)
    'api_url' => env('REMOTEOK_API_URL', 'https://remoteok.com/api'),
    'source_name' => env('REMOTEOK_SOURCE_NAME', 'remoteok'),
    // default valid through days
    'default_valid_days' => env('REMOTEOK_VALID_DAYS', 45),
    // HTTP client timeout seconds
    'http_timeout' => (int) env('REMOTEOK_HTTP_TIMEOUT', 15),
];

