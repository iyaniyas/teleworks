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
     * Saya gabungkan semua field dari dua versi.
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

        // complex types
        'applicant_location_requirements' => 'array',
        'raw' => 'array',

        // numeric salary
        'base_salary_min' => 'decimal:2',
        'base_salary_max' => 'decimal:2',
        'salary_min' => 'decimal:2',
        'salary_max' => 'decimal:2',
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
        });
    }

    /**
     * Scope: aktif = published + belum expired
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'published')
                     ->where('expires_at', '>', now());
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
        return $this->hasMany(\App\Models\Application::class);
    }
}

