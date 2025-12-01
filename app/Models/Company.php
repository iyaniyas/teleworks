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
        'description',
        'is_verified',
        'is_suspended',
    ];

    protected $casts = [
        'is_verified' => 'boolean',
        'is_suspended' => 'boolean',
    ];

    /**
     * Users relation (many-to-many via pivot company_user)
     * This must match User::companies() which uses belongsToMany(..., 'company_user')
     */
    public function users()
    {
        return $this->belongsToMany(\App\Models\User::class, 'company_user', 'company_id', 'user_id')
                    ->withPivot('role')   // hapus .withPivot jika pivot tidak punya kolom 'role'
                    ->withTimestamps();   // hapus .withTimestamps jika pivot tidak memiliki created_at/updated_at
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
}

