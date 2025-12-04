<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Report;
use Illuminate\Support\Facades\Auth;

class ReportController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->validate([
            'reportable_type' => 'required|string',
            'reportable_id' => 'required|integer',
            'reason' => 'required|string|max:2000',
        ]);

        $report = Report::create([
            'reportable_type' => $data['reportable_type'],
            'reportable_id' => $data['reportable_id'],
            'reporter_id' => Auth::id(),
            'reason' => $data['reason'],
            'status' => 'pending',
        ]);

        return back()->with('success', 'Terima kasih — laporan Anda telah dikirim.');
    }
}

