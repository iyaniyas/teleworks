<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * AdzunaClient
 *
 * Klien HTTP sederhana untuk Adzuna Jobs API.
 * - Default SELALU menggunakan keyword "remote"
 * - Param minimal: app_id, app_key, results_per_page, what="remote"
 * - Opsional: where, age (hanya dikirim jika > 0)
 * - Menghindari query kosong (tidak kirim ?where=&...)
 */
class AdzunaClient
{
    protected string $base;
    protected string $appId;
    protected string $appKey;
    protected int $per;

    public function __construct()
    {
        $this->base   = rtrim(config('services.adzuna.base', 'https://api.adzuna.com/v1/api/jobs'), '/');
        $this->appId  = (string) config('services.adzuna.app_id');
        $this->appKey = (string) config('services.adzuna.app_key');
        $this->per    = (int) config('services.adzuna.results_per_page', 50);
    }

    /**
     * Ambil 1 halaman hasil pencarian dari Adzuna.
     *
     * @param string $country Kode negara 2 huruf (mis: 'us', 'gb', 'sg')
     * @param int    $page    Nomor halaman (mulai dari 1)
     * @param array  $params  Opsi: what, where, age, results_per_page
     *                        NOTE: what akan di-force ke "remote" jika kosong.
     * @return array          JSON array hasil dari API (dengan key 'results')
     *
     * @throws \RuntimeException Jika HTTP gagal atau struktur respons tidak sesuai.
     */
    public function fetchPage(string $country, int $page = 1, array $params = []): array
    {
        $country = strtolower(trim($country));
        if ($country === '') {
            $country = 'us';
        }

        // Endpoint: /v1/api/jobs/{country}/search/{page}
        $url = sprintf('%s/%s/search/%d', $this->base, $country, $page);

        // Default keyword: "remote" (sesuai permintaan)
        $what = isset($params['what']) && trim((string) $params['what']) !== ''
            ? trim((string) $params['what'])
            : 'remote';

        // Susun query minimal, mirip contoh curl yang sukses
        $query = [
            'app_id'           => $this->appId,
            'app_key'          => $this->appKey,
            'results_per_page' => isset($params['results_per_page'])
                ? (int) $params['results_per_page']
                : $this->per,
            'what'  => $what,
            'where' => $params['where'] ?? null,
        ];

        // Hanya kirim age jika > 0 (beberapa kombinasi bisa memicu 400)
        if (!empty($params['age']) && (int) $params['age'] > 0) {
            $query['age'] = (int) $params['age'];
        }

        // Buang nilai null/empty agar tidak ada parameter kosong
        $query = array_filter($query, static fn($v) => $v !== null && $v !== '');

        // Logging request (opsional untuk debugging)
        Log::info('Adzuna request', ['url' => $url, 'query' => $query]);

        // Kirim request
        $response = Http::timeout(20)
            ->retry(2, 1000) // retry 2x jika ada error sementara
            ->acceptJson()
            ->withHeaders([
                // Sebagian WAF tidak suka default UA; set yang ramah
                'User-Agent' => 'TeleworksBot/1.0 (+https://teleworks.id)',
            ])
            ->get($url, $query);

        // Tangani kegagalan
        if ($response->failed()) {
            $snippet = mb_strimwidth($response->body() ?? '', 0, 800, '...');
            Log::error('Adzuna API error', [
                'status' => $response->status(),
                'url'    => $url,
                'query'  => $query,
                'body'   => $snippet,
            ]);
            throw new \RuntimeException('Adzuna API '.$response->status().': '.$snippet);
        }

        // Parse JSON
        $json = $response->json();

        // Validasi struktur dasar
        if (!is_array($json) || !array_key_exists('results', $json)) {
            $snippet = substr(json_encode($json, JSON_UNESCAPED_UNICODE), 0, 800);
            throw new \RuntimeException('Adzuna: unexpected response: '.$snippet);
        }

        return $json;
    }

    /**
     * Ambil detail job berdasarkan ad reference (adref).
     *
     * @param string $country Kode negara 2 huruf
     * @param string $adref   ID/AdRef job di Adzuna
     * @return array|null     Respons JSON atau null jika tidak ditemukan/gagal
     */
    public function fetchDetail(string $country, string $adref): ?array
    {
        $country = strtolower(trim($country));
        if ($country === '') {
            $country = 'us';
        }

        $url = sprintf('%s/%s/ad/%s', $this->base, $country, $adref);

        $resp = Http::timeout(20)
            ->acceptJson()
            ->withHeaders([
                'User-Agent' => 'TeleworksBot/1.0 (+https://teleworks.id)',
            ])
            ->get($url, [
                'app_id'  => $this->appId,
                'app_key' => $this->appKey,
            ]);

        if ($resp->failed()) {
            Log::warning('Adzuna detail fetch failed', [
                'status' => $resp->status(),
                'url'    => $url,
                'adref'  => $adref,
            ]);
            return null;
        }

        $data = $resp->json();
        return is_array($data) ? $data : null;
    }
}

