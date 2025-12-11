<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class JobApplication extends Model
{
    use HasFactory;

    protected $fillable = [
        'job_id',
        'user_id',
        'resume_path',
        'cover_letter',
        'status',

        // AI fields
        'ai_score',
        'ai_notes',
        'ai_scored_at',
    ];

    protected $casts = [
        'ai_scored_at' => 'datetime',
        'ai_score' => 'decimal:2',
    ];

    public function job()
    {
        return $this->belongsTo(\App\Models\Job::class);
    }

    public function user()
    {
        return $this->belongsTo(\App\Models\User::class);
    }
}

