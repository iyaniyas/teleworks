<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Job extends Model
{
    use HasFactory;

    protected $fillable = [
        'title', 'description', 'company', 'location', 'type',
        'is_wfh', 'search', 'source_url', 'raw_html',
        'is_imported', 'status', 'expires_at'
    ];

    protected $casts = [
        'is_wfh' => 'boolean',
	'expires_at' => 'datetime',
	'is_remote'                         => 'boolean',
        'direct_apply'                      => 'boolean',
        'date_posted'                       => 'date',
        'valid_through'                     => 'date',
        'applicant_location_requirements'   => 'array',
    ];

    // Auto tambahkan jam & expired default
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($job) {
            $hour = now()->format('H:i');
            $job->title .= ' ' . $hour;
            $job->description .= ' ' . $hour;

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

