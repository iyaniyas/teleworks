<?php

namespace App\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Psr\Http\Message\ResponseInterface;

class CareerjetClient
{
    protected Client $guzzle;
    protected string $apiKey;

    public function __construct()
    {
        $this->apiKey = env('CAREERJET_API_KEY', 'ad9ef6f623d24193053104da97070698');
        $this->guzzle = new Client([
            'http_errors' => false,
            'timeout' => 30,
        ]);
    }

    /**
     * Query Careerjet search API.
     *
     * Returns array (decoded JSON) on success, or ['__error' => true, 'http_code'=>..., 'body'=>...]
     *
     * @param array $params
     * @param string|null $referer
     * @param string|null $userAgent
     * @return array|null
     */
    public function query(array $params, ?string $referer = null, ?string $userAgent = null): ?array
    {
        $base = 'https://search.api.careerjet.net/v4/query';

        if (empty($this->apiKey)) {
            throw new \RuntimeException('CAREERJET_API_KEY not set');
        }

        $userAgent = $userAgent ?: ($params['user_agent'] ?? 'TeleworksBot/1.0');
        $referer = $referer ?: config('app.url', 'https://teleworks.id');

        $options = [
            'auth' => [$this->apiKey, ''],
            'query' => $params,
            'headers' => [
                'Accept' => 'application/json',
                'User-Agent' => $userAgent,
                'Referer' => $referer,
            ],
            'timeout' => 30,
        ];

        try {
            $resp = $this->guzzle->request('GET', $base, $options);
            $code = $resp->getStatusCode();
            $body = (string) $resp->getBody();

            \Log::debug('CareerjetClient.query', ['code'=>$code, 'body'=>mb_substr($body,0,4000), 'params'=>$params]);

            if ($code !== 200) {
                return ['__error'=>true, 'http_code'=>$code, 'body'=>$body];
            }

            $data = @json_decode($body, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                return ['__error'=>true, 'http_code'=>$code, 'body'=>$body, 'json_error'=>json_last_error_msg()];
            }

            return $data;
        } catch (RequestException $e) {
            $resp = $e->getResponse();
            $code = $resp ? $resp->getStatusCode() : null;
            $body = $resp ? (string)$resp->getBody() : $e->getMessage();
            \Log::error('CareerjetClient.query exception', ['msg'=>$e->getMessage(), 'code'=>$code, 'body'=>mb_substr($body,0,4000)]);
            return ['__error'=>true, 'http_code'=>$code, 'body'=>$body, 'exception'=>$e->getMessage()];
        }
    }

    /**
     * Follow a tracking URL and return final URL + body.
     * Uses allow_redirects and on_stats to capture effective url.
     *
     * @param string $url
     * @param string|null $referer
     * @param string|null $userAgent
     * @return array ['final_url'=>string|null, 'body'=>string|null, 'http_code'=>int|null]
     */
    public function fetchFinalUrlAndBody(string $url, ?string $referer = null, ?string $userAgent = null): array
    {
        $userAgent = $userAgent ?: 'TeleworksBot/1.0';
        $referer = $referer ?: config('app.url', 'https://teleworks.id');

        $effective = null;
        $httpCode = null;
        $body = null;

        try {
            $resp = $this->guzzle->request('GET', $url, [
                'headers' => [
                    'User-Agent' => $userAgent,
                    'Referer' => $referer,
                ],
                'allow_redirects' => [
                    'max' => 10,
                    'strict' => true,
                    'referer' => true,
                    'track_redirects' => true,
                ],
                'on_stats' => function (\GuzzleHttp\TransferStats $stats) use (&$effective) {
                    $effective = $stats->getEffectiveUri() ? (string)$stats->getEffectiveUri() : null;
                },
                'timeout' => 30,
            ]);

            $httpCode = $resp->getStatusCode();
            $body = (string) $resp->getBody();

            // If allow_redirects with track_redirects is used, Guzzle sets header X-Guzzle-Redirect-History and X-Guzzle-Redirect-Status-History
            // But we captured effective URL via on_stats.
            \Log::debug('CareerjetClient.fetchFinal', ['effective'=>$effective, 'code'=>$httpCode]);
            return ['final_url' => $effective, 'body' => $body, 'http_code' => $httpCode];
        } catch (RequestException $e) {
            $resp = $e->getResponse();
            $httpCode = $resp ? $resp->getStatusCode() : null;
            $body = $resp ? (string)$resp->getBody() : $e->getMessage();
            \Log::error('CareerjetClient.fetchFinal exception', ['msg'=>$e->getMessage(), 'code'=>$httpCode, 'url'=>$url]);
            return ['final_url' => $effective, 'body' => $body, 'http_code' => $httpCode, 'error'=>$e->getMessage()];
        }
    }
}

