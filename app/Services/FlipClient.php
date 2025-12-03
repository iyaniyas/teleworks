<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;

class FlipClient
{
    protected string $baseUrl;
    protected ?string $secretKey;
    protected int $timeout;

    public function __construct()
    {
        $env = config('flip.environment', 'sandbox');
        $this->baseUrl = $env === 'production' ? config('flip.production_base_url') : config('flip.sandbox_base_url');
        $this->secretKey = config('flip.secret_key');
        $this->timeout = config('flip.timeout', 30);

        if (empty($this->secretKey)) {
            Log::warning('Flip secret key is not configured.');
        }
    }

    protected function authHeader(): array
    {
        // Basic <Base64(secretKey + ":")>
        $token = base64_encode(($this->secretKey ?? '') . ':');
        return [
            'Authorization' => 'Basic ' . $token,
            'Content-Type' => 'application/x-www-form-urlencoded',
            'Accept' => 'application/json',
        ];
    }

    protected function url(string $path): string
    {
        return rtrim($this->baseUrl, '/') . '/' . ltrim($path, '/');
    }

    /**
     * Create a new payment (Accept Payments) - wrapper for Flip API.
     */
    public function createPayment(array $payload): array
    {
        // NOTE: adjust endpoint path to actual Flip doc if different
        $endpoint = '/v1/payments';
        $url = $this->url($endpoint);

        $response = Http::withHeaders($this->authHeader())
            ->timeout($this->timeout)
            ->asForm()
            ->post($url, $payload);

        if ($response->successful()) {
            return $response->json();
        }

        Log::error('Flip createPayment failed', [
            'url' => $url,
            'status' => $response->status(),
            'body' => $response->body(),
        ]);

        throw new Exception('Flip createPayment failed: ' . $response->body());
    }

    /**
     * Get payment status
     */
    public function getPaymentStatus(string $externalId): array
    {
        $endpoint = '/v1/payments/' . urlencode($externalId);
        $url = $this->url($endpoint);

        $response = Http::withHeaders($this->authHeader())
            ->timeout($this->timeout)
            ->acceptJson()
            ->get($url);

        if ($response->successful()) {
            return $response->json();
        }

        Log::error('Flip getPaymentStatus failed', [
            'url' => $url,
            'status' => $response->status(),
            'body' => $response->body(),
        ]);

        throw new Exception('Flip getPaymentStatus failed: ' . $response->body());
    }

    /**
     * Create disbursement (optional)
     */
    public function createDisbursement(array $payload): array
    {
        $endpoint = '/v1/disbursements';
        $url = $this->url($endpoint);

        $response = Http::withHeaders($this->authHeader())
            ->timeout($this->timeout)
            ->asForm()
            ->post($url, $payload);

        if ($response->successful()) {
            return $response->json();
        }

        Log::error('Flip createDisbursement failed', [
            'url' => $url,
            'status' => $response->status(),
            'body' => $response->body(),
        ]);

        throw new Exception('Flip createDisbursement failed: ' . $response->body());
    }
}

