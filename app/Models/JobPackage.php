<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class JobPackage extends Model
{
    protected $table = 'job_packages';

    protected $fillable = [
        'name', 'slug', 'price', 'duration_days', 'features', 'active'
    ];

    protected $casts = [
        'features' => 'array',
        'active' => 'boolean',
    ];
}

