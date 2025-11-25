<?php

namespace App\Services;

use App\Models\Job;
use Carbon\Carbon;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;

class RemoteOkImporter
{
    protected $apiUrl;
    protected $sourceName;
    protected $defaultValidDays;

    public function __construct()
    {
        $this->apiUrl = config('remoteok.api_url');
        $this->sourceName = config('remoteok.source_name');
        $this->defaultValidDays = config('remoteok.default_valid_days', 45);
    }

    /**
     * Fetch feed and import
     * @param string|null $url
     * @param int|null $limit
     * @return array summary
     */
    public function import(string $url = null, ?int $limit = null): array
    {
        $url = $url ?? $this->apiUrl;

        $response = Http::timeout(config('remoteok.http_timeout', 15))
            ->acceptJson()
            ->get($url);

        if (! $response->ok()) {
            Log::error('RemoteOkImporter: feed fetch failed', ['url'=>$url, 'status'=>$response->status()]);
            return ['status' => 'error', 'message' => 'fetch_failed', 'http_status' => $response->status()];
        }

        $payload = $response->json();

        if (! is_array($payload)) {
            Log::error('RemoteOkImporter: unexpected payload', []);
            return ['status'=>'error', 'message'=>'invalid_payload'];
        }

        $imported = 0; 
        $updated = 0; 
        $skipped = 0; 
        $errors = 0; 
        $processed = 0;

        foreach ($payload as $item) {

            if ($limit !== null && $processed >= $limit) {
                break;
            }

            if (! is_array($item)) { 
                $skipped++; 
                continue; 
            }

            if (isset($item['last_updated']) && isset($item['legal'])) {
                continue;
            }

            try {
                DB::beginTransaction();

                $mapped = $this->mapToJobArray($item);

                $existing = Job::where('source', $this->sourceName)
                    ->where('source_id', $mapped['source_id'])
                    ->first();

                if ($existing) {
                    $existing->fill($mapped);
                    $existing->save();
                    $updated++;
                } else {
                    Job::create($mapped);
                    $imported++;
                }

                DB::commit();
                $processed++;

            } catch (\Exception $e) {
                DB::rollBack();
                Log::error('RemoteOkImporter: import error', [
                    'error'=>$e->getMessage(),
                    'item'=>$item
                ]);
                $errors++;
            }
        }

        return compact('imported','updated','skipped','errors','processed');
    }

    protected function mapToJobArray(array $item): array
    {
        $stripHtml = function($html) {
            if (is_null($html)) return null;
            $text = trim(strip_tags($html));
            $text = preg_replace('/\s+/', ' ', $text);
            return $text;
        };

        $now = Carbon::now();

        $datePosted = null;
        if (!empty($item['date'])) {
            try { $datePosted = Carbon::parse($item['date'])->toDateString(); } catch (\Exception $e) {}
        }
        if (!$datePosted && !empty($item['epoch'])) {
            try { $datePosted = Carbon::createFromTimestampUTC($item['epoch'])->toDateString(); } catch (\Exception $e) {}
        }
        if (!$datePosted) {
            $datePosted = $now->toDateString();
        }

        $validThrough = null;
        if (!empty($item['validThrough'])) {
            try { $validThrough = Carbon::parse($item['validThrough'])->toDateString(); } catch (\Exception $e) {}
        }
        if (!$validThrough) {
            $validThrough = Carbon::parse($datePosted)
                ->addDays($this->defaultValidDays)
                ->toDateString();
        }

        $descriptionHtml = $item['description'] ?? ($item['body'] ?? null);
        $descriptionPlain = $stripHtml($descriptionHtml)
            ?: ($item['excerpt'] ?? null);

        $hiringOrg = $item['company'] ?? ($item['hiring_organization'] ?? null);
        $title = $item['position'] ?? $item['title'] ?? null;
        $location = $item['location'] ?? null;

        $applicantReq = $item['applicant_location_requirements'] ?? null;
        if (empty($applicantReq)) { 
            $applicantReq = 'Indonesia'; 
        }

        // Salary parsing
        $baseMin = null; 
        $baseMax = null; 
        $currency = null; 
        $baseString = null;

        if (!empty($item['salary_min']) || !empty($item['salary_max'])) {
            $baseMin = isset($item['salary_min']) ? floatval($item['salary_min']) : null;
            $baseMax = isset($item['salary_max']) ? floatval($item['salary_max']) : null;
            $baseString = ($baseMin || $baseMax)
                ? trim(($baseMin ?: '') . ($baseMax ? ' - '.$baseMax : ''))
                : null;
            $currency = $item['salary_currency'] ?? null;

        } elseif (!empty($item['salary']) || !empty($item['salary_str'])) {

            $baseString = $item['salary'] ?? $item['salary_str'];

            if (preg_match('/(USD|\$|GBP|£|EUR|IDR|Rp|AUD|CAD)/iu', $baseString, $m)) {
                $currency = $m[1];
            }
        }

        if (empty($baseString)) {
            $baseString = 'Perkiraan gaji';
        }

        $applyUrl = $item['apply_url'] ?? $item['url'] ?? null;
        $directApply = 0;

        if ($applyUrl) {
            $parsed = parse_url($applyUrl);
            $host = $parsed['host'] ?? '';
            if ($host && ! Str::contains($host, ['remoteok.com','remoteOK.com','remoteok.io'])) {
                $directApply = 1;
            }
        }

        $employmentType = $item['type'] ?? $item['employment_type'] ?? ($item['tags'] ?? null);
        if (is_array($employmentType)) {
            $employmentType = implode(', ', $employmentType);
        }

        $sourceId = $item['id'] ?? ($item['slug'] ?? null);

        $jobLocationType = 'Onsite';
        if (!empty($item['is_remote']) || (!empty($item['tags']) && in_array('remote', $item['tags']))) {
            $jobLocationType = 'Remote';
        } elseif (!empty($item['tags']) && in_array('hybrid', $item['tags'])) {
            $jobLocationType = 'Hybrid';
        }

        $mapped = [
            'title' => $title,
            'description' => mb_substr($descriptionPlain, 0, 65535),
            'description_html' => $descriptionHtml,
            'company' => $hiringOrg,
            'location' => $location,
            'type' => $item['tags'] ? implode(', ', $item['tags']) : null,
            'is_wfh' => !empty($item['is_remote']) ? 1 : 0,
            'search' => implode(' ', array_filter([$title, $hiringOrg, $location, $descriptionPlain])),
            'source_url' => $item['url'] ?? $applyUrl,
            'final_url' => $item['url'] ?? $applyUrl,
            'raw_html' => $descriptionHtml,
            'is_imported' => 1,
            'status' => 'published',
            'source' => $this->sourceName,
            'discovered_at' => now(),
            'posted_at' => Carbon::parse($datePosted)->toDateTimeString(),
            'source_id' => $sourceId,
            'date_posted' => $datePosted,
            'hiring_organization' => $hiringOrg,
            'job_location' => $location,
            'applicant_location_requirements' => $applicantReq,
            'base_salary_min' => $baseMin,
            'base_salary_max' => $baseMax,
            'base_salary_currency' => $currency,
            'base_salary_string' => $baseString,
            'direct_apply' => $directApply,
            'employment_type' => $employmentType,
            'employment_type_raw' => is_string($item['type'] ?? null)
                ? ($item['type'] ?? null)
                : json_encode($item['type'] ?? null),
            'identifier_name' => $this->sourceName,
            'identifier_value' => (string) $sourceId,
            'job_location_type' => $jobLocationType,
            'valid_through' => $validThrough,
            'is_remote' => $jobLocationType === 'Remote' ? 1 : 0,
            'apply_url' => $applyUrl,
            'easy_apply' => !empty($item['easy_apply']) ? 1 : 0,
            'raw' => json_encode($item),
        ];

        $mapped['fingerprint'] = hash(
            'sha256',
            $this->sourceName . ':' . ($sourceId ?? Str::random(8)) . ':' . ($title ?? '')
        );

        return $mapped;
    }
}

