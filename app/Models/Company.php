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

    // If your users table has company_id, you can keep this relation.
    public function users()
    {
        return $this->hasMany(\App\Models\User::class, 'company_id', 'id');
    }

    // If company ownership is via owner_id (your schema), owner relation:
    public function owner()
    {
        return $this->belongsTo(\App\Models\User::class, 'owner_id', 'id');
    }

    public function jobs()
    {
        return $this->hasMany(Job::class, 'company_id', 'id');
    }

    // Helper to return logo url (public storage)
    public function logoUrl()
    {
        if ($this->logo_path) {
            return asset('storage/'.$this->logo_path);
        }
        return asset('img/company-placeholder.png');
    }
}

