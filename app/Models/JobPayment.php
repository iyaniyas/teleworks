<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class JobPayment extends Model
{
    protected $table = 'job_payments';

    protected $fillable = [
        'job_id','company_id','package_id','external_id','amount','currency',
        'payment_gateway','status','transaction_id','started_at','expires_at','meta'
    ];

    protected $casts = [
        'meta' => 'array',
        'started_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    public function job()
    {
        return $this->belongsTo(Job::class);
    }

    public function package()
    {
        return $this->belongsTo(JobPackage::class, 'package_id');
    }
}

