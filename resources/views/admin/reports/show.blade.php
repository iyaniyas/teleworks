@extends('admin.layout')

@section('title','Report '.$report->id)

@section('content')
<h1>Report #{{ $report->id }}</h1>

<p><strong>Type:</strong> {{ class_basename($report->reportable_type) }}</p>
<p><strong>Reporter:</strong> {{ optional($report->reporter)->name ?? 'N/A' }}</p>
<p><strong>Reason:</strong></p>
<pre class="card card-body text-bg-dark">{{ $report->reason }}</pre>

@if($report->reportable)
    <h4 class="mt-3">Reportable</h4>
    <div class="card card-body text-bg-dark mb-3">
        <pre>{{ json_encode($report->reportable->toArray(), JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES) }}</pre>
    </div>
@endif

<form method="POST" action="{{ route('admin.reports.resolve', $report) }}">
    @csrf
    <div class="mb-3">
        <label class="form-label">Action</label>
        <select name="action" class="form-select" required>
            <option value="resolve">Resolve (action taken)</option>
            <option value="dismiss">Dismiss</option>
        </select>
    </div>

    <div class="mb-3">
        <label class="form-label">Notes</label>
        <textarea name="notes" class="form-control" rows="4" required></textarea>
    </div>

    <button class="btn btn-primary">Submit</button>
    <a class="btn btn-outline-light" href="{{ route('admin.reports.index') }}">Back</a>
</form>
@endsection

