<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SearchLog extends Model
{
    protected $table = 'search_logs';

    protected $fillable = [
        'q',
        'full_url',
        'params',
        'result_ids',
        'result_count',
        'user_ip',
        'user_agent',
    ];

    protected $casts = [
        'params' => 'array',
    ];
}

