@extends('admin.layout')

@section('title','Reports')

@section('content')
<h1 class="mb-4">Reports</h1>

<table class="table table-dark table-striped">
    <thead><tr><th>Type</th><th>Reportable</th><th>Reporter</th><th>Reason</th><th>Status</th><th>When</th><th>Actions</th></tr></thead>
    <tbody>
    @foreach($reports as $report)
        <tr>
            <td>{{ class_basename($report->reportable_type) }}</td>
            <td>
                @if($report->reportable)
                    <a href="#" onclick="window.open('{{ url('/') }}/admin/reports/{{ $report->id }}','_blank')">View</a>
                @else
                    — (deleted)
                @endif
            </td>
            <td>{{ optional($report->reporter)->name }}</td>
            <td style="max-width:300px;">{{ \Illuminate\Support\Str::limit($report->reason, 80) }}</td>
            <td>{{ $report->status }}</td>
            <td>{{ $report->created_at->diffForHumans() }}</td>
            <td>
                <a class="btn btn-sm btn-outline-light" href="{{ route('admin.reports.show', $report) }}">Open</a>
            </td>
        </tr>
    @endforeach
    </tbody>
</table>

{{ $reports->links() }}
@endsection

