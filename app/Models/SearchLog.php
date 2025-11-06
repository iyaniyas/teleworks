<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SearchLog extends Model
{
    protected $table = 'search_logs';
    protected $fillable = [
        'q', 'filters', 'results_count', 'ip', 'user_agent'
    ];
}

