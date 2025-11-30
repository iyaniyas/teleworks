<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Application extends Model
{
    protected $fillable = [
        'job_id','user_id','applicant_name','applicant_email','phone','cover_letter','cv_path','status','meta'
    ];

    protected $casts = [
        'meta' => 'array'
    ];

    public function job()
    {
        return $this->belongsTo(Job::class);
    }

    public function user()
    {
        return $this->belongsTo(\App\Models\User::class);
    }
}

