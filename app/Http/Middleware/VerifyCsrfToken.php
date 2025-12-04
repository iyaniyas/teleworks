<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware;

class VerifyCsrfToken extends Middleware
{
    /**
     * The URIs that should be excluded from CSRF verification.
     *
     * @var array<int, string>
     */
    protected $except = [
        //
        // MIDTRANS WEBHOOK
        // Webhook Midtrans tidak mengirim CSRF token, jadi kita kecualikan endpoint ini.
        // Jika Anda sudah memindahkan route webhook ke routes/api.php maka pengecualian ini
        // tidak wajib — tapi aman untuk ditambahkan (tidak berbahaya).
        //
        'api/midtrans/webhook',
        'midtrans/webhook',
        // beberapa variasi path (jika ada)
        'api/*/midtrans/webhook',
        '*/midtrans/webhook',
    ];
}

