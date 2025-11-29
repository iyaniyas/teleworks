<?php
namespace App\Http\Controllers\Seeker;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        $user = auth()->user();

        // simple recommended: latest 6 jobs (you can replace with skill matching later)
        $recommendedJobs = \App\Models\Job::latest()->take(6)->get();

        return view('seeker.dashboard', compact('recommendedJobs'));
    }
}

