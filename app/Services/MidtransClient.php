<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;

class MidtransClient
{
    protected string $snapBaseUrl;
    protected string $coreBaseUrl;
    protected ?string $serverKey;
    protected int $timeout;

    public function __construct()
    {
        $env = config('midtrans.environment', 'sandbox');

        // Base URL untuk Snap (popup / redirect)
        $this->snapBaseUrl = $env === 'production'
            ? rtrim(config('midtrans.production_base_url', 'https://app.midtrans.com'), '/')
            : rtrim(config('midtrans.sandbox_base_url', 'https://app.sandbox.midtrans.com'), '/');

        // Base URL untuk Core API (v2 status & lain-lain)
        $this->coreBaseUrl = $env === 'production'
            ? rtrim(config('midtrans.core_production_base_url', 'https://api.midtrans.com'), '/')
            : rtrim(config('midtrans.core_sandbox_base_url', 'https://api.sandbox.midtrans.com'), '/');

        $this->serverKey = config('midtrans.server_key');
        $this->timeout   = (int) config('midtrans.timeout', 30);

        if (empty($this->serverKey)) {
            Log::warning('Midtrans server key is not configured (midtrans.server_key).');
        }

        Log::info('MidtransClient initialized', [
            'environment'   => $env,
            'snap_base_url' => $this->snapBaseUrl,
            'core_base_url' => $this->coreBaseUrl,
        ]);
    }

    protected function authHeader(): array
    {
        // Basic Auth: ServerKey:
        $token = base64_encode(($this->serverKey ?? '') . ':');

        return [
            'Authorization' => 'Basic ' . $token,
            'Accept'        => 'application/json',
            'Content-Type'  => 'application/json',
        ];
    }

    protected function snapUrl(string $path): string
    {
        return $this->snapBaseUrl . '/' . ltrim($path, '/');
    }

    protected function coreUrl(string $path): string
    {
        return $this->coreBaseUrl . '/' . ltrim($path, '/');
    }

    /**
     * Create a Snap transaction and return the response (assoc array).
     * Response typically contains 'token' and 'redirect_url'.
     *
     * Docs:
     *  - Sandbox:   POST https://app.sandbox.midtrans.com/snap/v1/transactions
     *  - Production: POST https://app.midtrans.com/snap/v1/transactions
     *
     * See: https://docs.midtrans.com/docs/snap-snap-integration-guide
     */
    public function createTransaction(array $payload): array
    {
        $endpoint = config('midtrans.snap_endpoint', '/snap/v1/transactions');
        $url      = $this->snapUrl($endpoint);

        $response = Http::withHeaders($this->authHeader())
            ->timeout($this->timeout)
            ->post($url, $payload);

        if ($response->successful()) {
            return $response->json();
        }

        // Log detailed info for debugging
        Log::error('Midtrans createTransaction failed', [
            'url'    => $url,
            'status' => $response->status(),
            'body'   => $response->body(),
        ]);

        throw new Exception('Midtrans createTransaction failed: ' . $response->body());
    }

    /**
     * Get transaction status via v2 status endpoint.
     *
     * Docs:
     *  - Sandbox:    GET https://api.sandbox.midtrans.com/v2/{order_id}/status
     *  - Production: GET https://api.midtrans.com/v2/{order_id}/status
     */
    public function getStatus(string $orderId): array
    {
        $endpoint = '/v2/' . urlencode($orderId) . '/status';
        $url      = $this->coreUrl($endpoint);

        $response = Http::withHeaders($this->authHeader())
            ->timeout($this->timeout)
            ->get($url);

        if ($response->successful()) {
            return $response->json();
        }

        Log::error('Midtrans getStatus failed', [
            'url'    => $url,
            'status' => $response->status(),
            'body'   => $response->body(),
        ]);

        throw new Exception('Midtrans getStatus failed: ' . $response->body());
    }
}

