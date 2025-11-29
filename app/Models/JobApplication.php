<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class JobApplication extends Model
{
    use HasFactory;

    protected $fillable = [
        'job_id','user_id','resume_path','cover_letter','status'
    ];

    public function job()
    {
        return $this->belongsTo(\App\Models\Job::class);
    }

    public function user()
    {
        return $this->belongsTo(\App\Models\User::class);
    }

    // app/Models/User.php
public function applications()
{
    return $this->hasMany(\App\Models\JobApplication::class);
}

}

