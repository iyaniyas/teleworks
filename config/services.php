<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
     */
    'theirstack' => [
      'base_url' => env('THEIRSTACK_BASE_URL', 'https://api.theirstack.com'),
      'key'      => env('THEIRSTACK_API_KEY'),
    ],

	
    'adzuna' => [
      'app_id' => env('ADZUNA_APP_ID'),
      'app_key' => env('ADZUNA_APP_KEY'),
      'country' => env('ADZUNA_COUNTRY', 'us'),
      'results_per_page' => env('ADZUNA_RESULTS_PER_PAGE', 50),
      'base' => 'https://api.adzuna.com/v1/api/jobs',
    ],

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
        'scheme' => 'https',
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

];
