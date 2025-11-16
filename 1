<?php

return [

    /*
    |--------------------------------------------------------------------------
    | HTMLPurifier Encoding & Finalize
    |--------------------------------------------------------------------------
    |
    | 'encoding' must match your app encoding (usually UTF-8).
    | 'finalize' when true will freeze the config (better for performance).
    |
    */

    'encoding' => 'UTF-8',
    'finalize' => true,
    'cachePath' => storage_path('app/purifier'), // where HTMLPurifier cache/serializer stored
    'cacheFileMode' => 0644,

    /*
    |--------------------------------------------------------------------------
    | Default settings and named profiles
    |--------------------------------------------------------------------------
    |
    | 'settings' is an array of named profiles. The 'default' profile is used
    | when no profile name is passed to Purifier::clean(...).
    |
    */

    'settings' => [

        'default' => [
            'HTML.Doctype'             => 'HTML 4.01 Transitional',
            'HTML.Allowed'            => 'p,br,ul,ol,li,strong,b,em,i,a[href|rel|target]',
            'AutoFormat.AutoParagraph'=> false,
            'AutoFormat.RemoveEmpty'  => true,
            'URI.AllowedSchemes'      => [
                'http'  => true,
                'https' => true,
            ],
            // disable ID attributes and iframes for safety
            'Attr.EnableID'           => false,
            'HTML.SafeIframe'         => false,
        ],

        /*
        |-----------------------------------------------------------------------
        | teleworks profile (used by importer to sanitize job descriptions)
        |-----------------------------------------------------------------------
        | - tight whitelist of tags,
        | - only http/https URIs,
        | - remove empty elements,
        | - keep no event handlers or style attributes.
        |
        */

        'teleworks' => [
            'HTML.Doctype'             => 'HTML 4.01 Transitional',
            'HTML.Allowed'            => 'p,br,ul,ol,li,strong,b,em,i,a[href|rel|target]',
            'AutoFormat.AutoParagraph'=> false,
            'AutoFormat.RemoveEmpty'  => true,
            'Attr.EnableID'           => false,
            'HTML.SafeIframe'         => false,
            'URI.AllowedSchemes'      => [
                'http'  => true,
                'https' => true,
            ],
            // Disallow style attributes and on* handlers by default.
            // HTMLPurifier will strip those if not explicitly allowed.
            // Extra strict: force rel & target settings on links in your code.
        ],
    ],
];

