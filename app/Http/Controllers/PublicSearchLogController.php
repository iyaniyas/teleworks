<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\SearchLog;

class PublicSearchLogController extends Controller
{
    public function index(Request $request)
    {
        $perPage = 50;
        $q = trim($request->query('q', ''));

        $logs = SearchLog::query()
            ->when($q !== '', function ($s) use ($q) {
                $s->where('q', 'like', "%{$q}%");
            })
            ->orderByDesc('created_at')
            ->paginate($perPage)
            ->withQueryString();

        return view('public_search_logs', [
            'logs' => $logs,
            'q' => $q,
        ]);
    }
}

