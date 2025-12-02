<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\SearchLog;
use Illuminate\Support\Facades\Cache;

class PublicSearchLogController extends Controller
{
    public function index(Request $request)
    {
        $perPage = 50;
        $q = trim($request->query('q', ''));

        // -------------------------------
        // Cache key unik per halaman + query
        // -------------------------------
        $page = max(1, (int)$request->query('page', 1));
        $cacheKey = "public_search_logs:q={$q}:page={$page}:per={$perPage}";

        // -------------------------------
        // Ambil dari cache 12 jam (Redis)
        // -------------------------------
        $logs = Cache::store('redis')->remember($cacheKey, now()->addHours(12), function () use ($q, $perPage) {
            return SearchLog::query()
                ->when($q !== '', function ($s) use ($q) {
                    $s->where('q', 'like', "%{$q}%");
                })
                ->orderByDesc('created_at')
                ->paginate($perPage)
                ->withQueryString();
        });

        return view('public_search_logs', [
            'logs' => $logs,
            'q'    => $q,
        ]);
    }
}

