<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Job extends Model
{
    use HasFactory;

    protected $table = 'jobs';

    /**
     * Jika Anda lebih suka whitelist, gunakan fillable.
     * Saya gabungkan semua field dari dua versi dan menambahkan kolom AI.
     */
    protected $guarded = ['id'];

    protected $fillable = [
        // employer-specific / local app fields
        'company_id','title','description','location','remote','salary_min','salary_max','status','expires_at','views',

        // core / original fields
        'company', 'company_domain',
        'apply_url', 'final_url', 'url_source',
        'posted_at', 'fingerprint', 'source', 'discovered_at', 'easy_apply', 'location', 'type',
        'is_wfh', 'search', 'source_url', 'raw_html',
        'is_imported', 'status', 'expires_at',

        // theirstack-specific / importer fields
        'date_posted',
        'hiring_organization',
        'job_location',
        'applicant_location_requirements',
        'base_salary_min',
        'base_salary_max',
        'base_salary_currency',
        'base_salary_unit',
        'base_salary_string',
        'direct_apply',
        'employment_type',
        'employment_type_raw',
        'identifier_name',
        'identifier_value',
        'job_location_type',
        'valid_through',
        'is_remote',
        'raw',

        // html description (sanitized)
        'description_html',

        // AI fields
        'ai_generate_count',
        'ai_applicants_summary',
        'ai_applicants_summary_count',
        'ai_applicants_summary_last_at',
    ];

    /**
     * Default attribute values untuk kolom AI supaya kebaca saat model baru dibuat.
     */
    protected $attributes = [
        'ai_generate_count' => 0,
        'ai_applicants_summary' => null,
        'ai_applicants_summary_count' => 0,
    ];

    protected $casts = [
        // boolean flags (kept both names for backward compatibility)
        'is_wfh' => 'boolean',
        'is_remote' => 'boolean',
        'remote' => 'boolean',
        'direct_apply' => 'boolean',
        'easy_apply' => 'boolean',

        // dates & datetimes
        'expires_at' => 'datetime',
        'discovered_at' => 'datetime',
        'posted_at' => 'datetime',
        'date_posted' => 'date',
        'valid_through' => 'date',
        'ai_applicants_summary_last_at' => 'datetime',

        // complex types
        'applicant_location_requirements' => 'array',
        'raw' => 'array',

        // numeric salary
        'base_salary_min' => 'decimal:2',
        'base_salary_max' => 'decimal:2',
        'salary_min' => 'decimal:2',
        'salary_max' => 'decimal:2',

        // AI numeric fields
        'ai_generate_count' => 'integer',
        'ai_applicants_summary_count' => 'integer',
    ];

    /**
     * Boot logic: tetap mempertahankan logic dari versi awal Anda.
     * (Menambahkan jam ke title/description jika belum ada dan set default expires_at)
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($job) {
            // tambahkan jam sekarang ke title/description jika belum berakhiran jam (sesuai kode asli)
            $hour = now()->format('H:i');

            if (!empty($job->title) && !str_ends_with($job->title, " {$hour}")) {
                $job->title = $job->title . ' ' . $hour;
            }

            if (!empty($job->description) && !str_ends_with($job->description, " {$hour}")) {
                $job->description = $job->description . ' ' . $hour;
            }

            if (!$job->expires_at) {
                $job->expires_at = now()->addDays(45);
            }

            $type = strtolower($job->type ?? '');
            if (str_contains($type, 'wfh') || str_contains($type, 'remote')) {
                $job->is_wfh = true;
                $job->is_remote = true;
                $job->remote = true;
            }

            // pastikan kolom AI ada default (jika belum diatur)
            $job->ai_generate_count = $job->ai_generate_count ?? 0;
            $job->ai_applicants_summary_count = $job->ai_applicants_summary_count ?? 0;
        });
    }

    /**
     * Scope: aktif = published + belum expired (atau tanpa expires_at)
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'published')
                     ->where(function ($q) {
                         $q->whereNull('expires_at')
                           ->orWhere('expires_at', '>', now());
                     });
    }

    /**
     * Loker yang boleh dilihat publik:
     * - published
     * - expired
     */
    public function scopePublicVisible($query)
    {
        return $query->whereIn('status', ['published', 'expired']);
    }

    /**
     * Relasi ke perusahaan
     */
    public function company()
    {
        return $this->belongsTo(\App\Models\Company::class);
    }

    /**
     * Relasi ke aplikasi (job applications)
     * Pastikan model App\Models\Application ada / ganti namespace sesuai app Anda.
     */
    public function applications()
    {
        return $this->hasMany(\App\Models\JobApplication::class);
    }

    /**
     * Helper: increment ai_generate_count (mis. saat AI menghasilkan ringkasan/deskripsi)
     */
    public function incrementAiGenerateCount(int $by = 1)
    {
        $this->increment('ai_generate_count', $by);
        return $this;
    }

    /**
     * Helper: update ringkasan pelamar yang dihasilkan AI
     *
     * @param string|null $summary
     * @param int $applicantCount
     * @return $this
     */
    public function updateAiApplicantsSummary(?string $summary, int $applicantCount = 0)
    {
        $this->ai_applicants_summary = $summary;
        $this->ai_applicants_summary_count = $applicantCount;
        $this->ai_applicants_summary_last_at = now();
        $this->save();
        return $this;
    }

    public function applicationsWithAi()
	{
    return $this->applications()
        ->whereNotNull('ai_score')
        ->with('user')
        ->orderByDesc('ai_score');
	}	

    public function buildApplicantsAiSummary(): array
	{
    $apps = $this->applicationsWithAi()->get();

    if ($apps->isEmpty()) {
        return [
            'total' => 0,
            'avg_score' => null,
            'top3' => [],
            'summary_text' => 'Belum ada pelamar yang dinilai AI.',
        ];
    }

    $avg = round($apps->avg('ai_score'), 2);
    $top3 = $apps->take(3);

    $summaryText = "Total {$apps->count()} pelamar dinilai AI. "
        . "Rata-rata skor {$avg}. "
        . "Kandidat teratas memiliki skor {$top3->first()->ai_score}.";

    return [
        'total' => $apps->count(),
        'avg_score' => $avg,
        'top3' => $top3,
        'summary_text' => $summaryText,
    ];
	}

}

