<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Job extends Model
{
    use HasFactory;

    protected $fillable = [
        // core
        'title', 'description', 'company', 'company_domain',
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
        'apply_url',
        'easy_apply',
        'raw',
        'fingerprint',

        // html description (sanitized)
        'description_html',
    ];

    protected $casts = [
        'is_wfh' => 'boolean',
        'expires_at' => 'datetime',
        'is_remote' => 'boolean',
        'direct_apply' => 'boolean',
        'date_posted' => 'date',
        'valid_through' => 'date',
        'applicant_location_requirements' => 'array',
        'raw' => 'array',
        'discovered_at' => 'datetime',
        'posted_at' => 'datetime',
        'base_salary_min' => 'decimal:2',
        'base_salary_max' => 'decimal:2',
    ];

    // Auto tambahkan jam & expired default (keputusan Anda; saya biarkan)
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($job) {
            // hanya tambahkan hour jika belum memiliki jam tambahan — ini mengikuti perilaku original Anda
            $hour = now()->format('H:i');

            if (!str_ends_with($job->title ?? '', " {$hour}")) {
                $job->title = ($job->title ?? '') . ' ' . $hour;
            }
            if (!str_ends_with($job->description ?? '', " {$hour}")) {
                $job->description = ($job->description ?? '') . ' ' . $hour;
            }

            if (!$job->expires_at) {
                $job->expires_at = now()->addDays(45);
            }

            $type = strtolower($job->type ?? '');
            if (str_contains($type, 'wfh') || str_contains($type, 'remote')) {
                $job->is_wfh = true;
            }
        });
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'published')
                     ->where('expires_at', '>', now());
    }
}

