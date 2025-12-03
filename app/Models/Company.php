<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Company extends Model
{
    protected $table = 'companies';

    protected $fillable = [
        'owner_id',
        'name',
        'slug',
        'domain',
        'logo_path',
        'website',
        'location',
        'description',
        'size',
        'industry',
        'founded_at'
    ];

    public function users()
    {
        return $this->belongsToMany(\App\Models\User::class, 'company_user')->withPivot('role')->withTimestamps();
    }

    /**
     * If company ownership is via owner_id (your schema), owner relation:
     */
    public function owner()
    {
        return $this->belongsTo(\App\Models\User::class, 'owner_id', 'id');
    }

    /**
     * Jobs relation: one company has many jobs
     */
    public function jobs()
    {
        return $this->hasMany(\App\Models\Job::class, 'company_id', 'id');
    }

    /**
     * Helper to return logo url (public storage)
     */
    public function logoUrl()
    {
        if ($this->logo_path) {
            return asset('storage/'.$this->logo_path);
        }
        return asset('img/company-placeholder.png');
    }

    /**
     * Return the latest active JobPayment for this company (status=paid and not expired)
     */
    public function activePackage()
    {
        return \App\Models\JobPayment::where('company_id', $this->id)
            ->where('status', 'paid')
            ->whereNotNull('expires_at')
            ->where('expires_at', '>', now())
            ->orderByDesc('expires_at')
            ->first();
    }

    public function hasActivePackage(): bool
    {
        return (bool) $this->activePackage();
    }

    public function activePackageDaysLeft(): ?int
    {
        $pkg = $this->activePackage();
        if (!$pkg || !$pkg->expires_at) return null;
        return now()->diffInDays($pkg->expires_at, false);
    }
}

