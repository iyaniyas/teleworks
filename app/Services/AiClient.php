<?php

namespace App\Services;

use App\Models\Job;
use App\Models\JobApplication;
use App\Models\Company;
use App\Models\Profile;
use App\Models\Resume;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class AiClient
{
    protected string $provider;
    protected array $config;

    public function __construct()
    {
        $this->provider = config('ai.provider', 'deepseek');
        $this->config   = config('ai.' . $this->provider, []);
    }

    /**
     * Generate job description terstruktur:
     * Tanggung Jawab / Persyaratan / Keuntungan / Mengapa Bergabung / Tentang Kami
     */
    public function generateJobDescription(Job $job, ?Company $company = null): ?string
    {
        if ($this->provider !== 'deepseek') {
            return null;
        }

        $apiKey  = $this->config['api_key'] ?? null;
        $baseUrl = rtrim($this->config['base_url'] ?? '', '/');
        $model   = $this->config['model'] ?? 'deepseek-chat';

        if (!$apiKey || !$baseUrl) {
            Log::warning('AiClient: missing DeepSeek config');
            return null;
        }

        $title           = $job->title ?? '';
        $employmentType  = $job->employment_type ?? '';
        $location        = $job->location ?? '';
        $isRemote        = isset($job->is_remote) && $job->is_remote ? 'Ya (Remote)' : 'Tidak (On-site / Hybrid)';
        $salaryMin       = isset($job->base_salary_min) && $job->base_salary_min ? number_format((float)$job->base_salary_min, 0, ',', '.') : null;
        $salaryMax       = isset($job->base_salary_max) && $job->base_salary_max ? number_format((float)$job->base_salary_max, 0, ',', '.') : null;
        $companyName     = isset($company->name) ? $company->name : ($job->company ?? '');
        $companyDesc     = isset($company->description) ? $company->description : '';

        $salaryText = null;
        if ($salaryMin && $salaryMax) {
            $salaryText = "Perkiraan gaji: Rp{$salaryMin} - Rp{$salaryMax} per bulan.";
        } elseif ($salaryMin) {
            $salaryText = "Perkiraan gaji mulai dari Rp{$salaryMin} per bulan.";
        }

        $prompt = <<<PROMPT
Tulis deskripsi lowongan kerja dalam Bahasa Indonesia yang profesional dan jelas.

Output HARUS dalam format teks dengan heading berikut secara berurutan:

Tanggung Jawab:
- poin-poin...

Persyaratan:
- poin-poin...

Keuntungan:
- poin-poin...

Mengapa Bergabung Dengan Kami?
- poin-poin...

Tentang Kami:
paragraf...

Detail lowongan:
- Judul: {$title}
- Tipe Pekerjaan: {$employmentType}
- Lokasi kerja: {$location}
- Remote: {$isRemote}
- {$salaryText}

Tentang perusahaan (jika ada):
{$companyName}
{$companyDesc}

Fokus: kerja remote / hybrid, lingkungan profesional, relevan untuk pasar Indonesia, singkat tapi padat.
PROMPT;

        try {
            $response = Http::withToken($apiKey)
                ->timeout($this->config['timeout'] ?? 15)
                ->post("{$baseUrl}/chat/completions", [
                    'model' => $model,
                    'messages' => [
                        [
                            'role' => 'system',
                            'content' => 'Kamu adalah asisten HR yang menulis deskripsi lowongan kerja profesional dalam Bahasa Indonesia.',
                        ],
                        [
                            'role' => 'user',
                            'content' => $prompt,
                        ],
                    ],
                    'temperature' => 0.5,
                ]);

            if (!$response->successful()) {
                Log::warning('AiClient::generateJobDescription failed', [
                    'status' => $response->status(),
                    'body'   => $response->body(),
                ]);
                return null;
            }

            $data = $response->json();
            $text = $data['choices'][0]['message']['content'] ?? null;

            return $text ? trim($text) : null;
        } catch (\Throwable $e) {
            Log::error('AiClient::generateJobDescription exception', [
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Skor satu lamaran berdasarkan CV + job description.
     * Menulis nilai ke kolom ai_score, ai_notes, ai_scored_at.
     */
    public function scoreApplication(JobApplication $application): void
    {
        if ($this->provider !== 'deepseek') {
            return;
        }

        $job  = $application->job;
        $user = $application->user;

        if (!$job || !$user) {
            return;
        }

        // ambil profile kalau ada
        $profile = Profile::where('user_id', $user->id)->first();
        $resume  = null;

        // kalau kamu pakai tabel resumes aktif, bisa tarik di sini
        if (class_exists(Resume::class)) {
            $resume = Resume::where('user_id', $user->id)
                ->where('is_active', true)
                ->first();
        }

        // Saat ini kita tidak parse file CV beneran (butuh lib parsing PDF/Word),
        // jadi kita pakai placeholder dan metadata. Nanti bisa kamu upgrade.
        $cvInfo = [];

        if ($resume && isset($resume->title)) {
            $cvInfo[] = 'Judul CV: ' . $resume->title;
        }

        // build CV info safely (avoid ?? operator)
        $cvSummary = '';
        if (isset($profile) && is_object($profile) && isset($profile->summary)) {
            $cvSummary = $profile->summary;
        }
        if ($cvSummary !== '') {
            $cvInfo[] = 'Ringkasan: ' . $cvSummary;
        }

        if (isset($profile) && is_object($profile)) {
            if (isset($profile->headline) && $profile->headline !== '') {
                $cvInfo[] = 'Headline: ' . $profile->headline;
            }
            if (isset($profile->location) && $profile->location !== '') {
                $cvInfo[] = 'Lokasi kandidat: ' . $profile->location;
            }
            if (isset($profile->skills) && is_array($profile->skills) && count($profile->skills) > 0) {
                $cvInfo[] = 'Skill: ' . implode(', ', $profile->skills);
            }
        }

        $cvInfoText = implode("\n", array_filter($cvInfo));

        // build job text safely
        $jobTitle = isset($job->title) ? $job->title : '';
        $jobDescription = isset($job->description) ? $job->description : '';
        $jobLocation = isset($job->location) ? $job->location : '';
        $jobEmploymentType = isset($job->employment_type) ? $job->employment_type : '';
        $isRemote = (isset($job->is_remote) && $job->is_remote) ? 'Remote' : 'On-site/Hybrid';

        $jobText = "Judul: {$jobTitle}\n\nDeskripsi:\n\n{$jobDescription}\n\n\nLokasi: {$jobLocation}\n\nTipe: {$jobEmploymentType}\n\nRemote: {$isRemote}\n";

        // cover letter safe
        $coverLetter = isset($application->cover_letter) ? $application->cover_letter : '';

        // prompt (heredoc kept, but variables precomputed to avoid inline ?? or ?->)
        $prompt = <<<PROMPT
Anda adalah asisten HR yang menilai kecocokan kandidat terhadap satu lowongan kerja.

Berikut detail lowongan:

{$jobText}

Berikut informasi kandidat:

Nama: {$user->name}
Email: {$user->email}

Profil & CV (ringkasan):
{$cvInfoText}

Surat lamaran (jika ada):
{$coverLetter}

Tugas Anda:
1. Berikan skor kecocokan kandidat terhadap lowongan dari 0 sampai 100.
2. Jelaskan secara singkat:
   - Kekuatan utama kandidat
   - Kekurangan kandidat / risiko
3. Fokus pada relevansi skill, pengalaman, dan kesiapan untuk kerja remote/WFH.

Format output HARUS JSON valid tanpa penjelasan tambahan, dengan struktur:
{
  "score": 0-100 (number),
  "strengths": ["...","..."],
  "weaknesses": ["...","..."],
  "summary": "ringkasan singkat max 3 kalimat"
}
PROMPT;

        $apiKey  = $this->config['api_key'] ?? null;
        $baseUrl = rtrim($this->config['base_url'] ?? '', '/');
        $model   = $this->config['model'] ?? 'deepseek-chat';

        if (!$apiKey || !$baseUrl) {
            Log::warning('AiClient: missing DeepSeek config for scoring');
            return;
        }

        try {
            $response = Http::withToken($apiKey)
                ->timeout($this->config['timeout'] ?? 20)
                ->post("{$baseUrl}/chat/completions", [
                    'model' => $model,
                    'messages' => [
                        [
                            'role' => 'system',
                            'content' => 'Kamu adalah asisten HR yang ketat tetapi adil. Jawab SELALU dalam JSON valid.',
                        ],
                        [
                            'role' => 'user',
                            'content' => $prompt,
                        ],
                    ],
                    'temperature' => 0.3,
                ]);

            if (!$response->successful()) {
                Log::warning('AiClient::scoreApplication failed', [
                    'status' => $response->status(),
                    'body'   => $response->body(),
                ]);
                return;
            }

            $data = $response->json();
            $text = $data['choices'][0]['message']['content'] ?? null;
            if (!$text) {
                return;
            }

            // coba parse JSON
            $jsonStart = strpos($text, '{');
            $jsonEnd   = strrpos($text, '}');
            if ($jsonStart === false || $jsonEnd === false) {
                return;
            }

            $jsonString = substr($text, $jsonStart, $jsonEnd - $jsonStart + 1);
            $parsed = json_decode($jsonString, true);

            if (!is_array($parsed) || !isset($parsed['score'])) {
                return;
            }

            $score = (float)$parsed['score'];
            $strengths = isset($parsed['strengths']) && is_array($parsed['strengths'])
                ? $parsed['strengths']
                : [];
            $weaknesses = isset($parsed['weaknesses']) && is_array($parsed['weaknesses'])
                ? $parsed['weaknesses']
                : [];
            $summary = $parsed['summary'] ?? '';

            $notesParts = [];
            if ($summary) {
                $notesParts[] = "Ringkasan: " . $summary;
            }
            if ($strengths) {
                $notesParts[] = "Kekuatan: " . implode('; ', $strengths);
            }
            if ($weaknesses) {
                $notesParts[] = "Kelemahan: " . implode('; ', $weaknesses);
            }

            $application->ai_score    = $score;
            $application->ai_notes    = implode("\n", array_filter($notesParts));
            $application->ai_scored_at = now();
            $application->save();
        } catch (\Throwable $e) {
            Log::error('AiClient::scoreApplication exception', [
                'error' => $e->getMessage(),
                'application_id' => $application->id,
            ]);
        }
    }
}

