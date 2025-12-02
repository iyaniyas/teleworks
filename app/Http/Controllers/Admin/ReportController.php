<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Report;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function index()
    {
        $reports = Report::with('reportable','reporter')->orderBy('created_at','desc')->paginate(25);
        return view('admin.reports.index', compact('reports'));
    }

    public function show(Report $report)
    {
        return view('admin.reports.show', compact('report'));
    }

    public function resolve(Request $request, Report $report)
    {
        $data = $request->validate([
            'action' => 'required|in:resolve,dismiss',
            'notes' => 'required|string|max:2000',
        ]);

        $report->status = $data['action'] === 'resolve' ? 'resolved' : 'dismissed';
        $report->resolved_by = auth()->id();
        $report->resolved_at = now();
        $report->notes = $data['notes'];
        $report->save();

        // Optional: perform action on reportable (e.g., soft delete job). We won't auto-delete anything.
        return redirect()->route('admin.reports.index')->with('success', 'Report updated.');
    }
}

