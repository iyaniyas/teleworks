<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class TheirStackClient
{
    protected string $baseUrl;
    protected string $apiKey;
    protected int $maxAgeDays;

    public function __construct()
    {
        $this->baseUrl    = rtrim(env('THEIRSTACK_BASE_URL', 'https://api.theirstack.com'), '/').'/v1';
        $this->apiKey     = (string) env('THEIRSTACK_API_KEY', '');
        $this->maxAgeDays = (int) env('THEIRSTACK_MAX_AGE_DAYS', 30); // default 30 hari terakhir
    }

    /**
     * POST /v1/jobs/search
     * - Pagination: offset + limit (limit <= 50)
     * - WAJIB: salah satu dari posted_at_* atau company/job filter
     */
    public function searchJobs(array $filters = [], int $page = 0, int $limit = 25): array
    {
        $limit  = min(50, max(1, (int) $limit));
        $offset = max(0, (int) $page) * $limit;

        $q          = $filters['q'] ?? null;
        $country    = $filters['country_code'] ?? null;
        $remote     = array_key_exists('remote', $filters) ? (bool) $filters['remote'] : null;
        $company    = $filters['company_name'] ?? null; // opsional, kalau mau filter perusahaan

        $payload = [
            // pagination
            'offset' => $offset,
            'limit'  => $limit,

            // ✅ penuhi syarat validasi (pilih satu): pakai max age (hari)
            'posted_at_max_age_days' => $this->maxAgeDays,

            // urutkan terbaru
            'order_by' => [
                ['desc' => true, 'field' => 'date_posted'],
                ['desc' => true, 'field' => 'discovered_at'],
            ],

            // filter umum
            'remote'              => $remote,                 // true/false/null
            'job_country_code_or' => $country ? [$country] : [],

            // keyword (opsional)
            'job_title_pattern_or'                       => $q ? [$q] : [],
            'job_description_pattern_or'                 => $q ? [$q] : [],
            'job_description_pattern_is_case_insensitive'=> true,
        ];

        // (opsional) filter perusahaan kalau dikirim
        if (!empty($company)) {
            $payload['company_name_or'] = [$company];
        }

        $resp = Http::withHeaders([
                'Authorization' => 'Bearer '.$this->apiKey,
                'Accept'        => 'application/json',
            ])
            ->post($this->baseUrl.'/jobs/search', $payload);

        if ($resp->failed()) {
            // bantu debug di CLI
            dump([
                'status'   => $resp->status(),
                'payload'  => $payload,
                'response' => $resp->json(),
            ]);
        }

        $resp->throw();
        return $resp->json() ?? [];
    }
}

