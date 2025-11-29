<?php

namespace App\Http\Controllers\Employer;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        // Simple placeholder view or string. Create a view later if mau.
        return view('employer.dashboard'); // kita buat view sederhana di langkah 3
        // atau sementara: return 'Employer dashboard (placeholder)';
    }
}

