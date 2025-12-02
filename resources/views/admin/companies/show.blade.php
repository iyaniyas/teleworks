@extends('admin.layout')

@section('title','Company: '.$company->name)

@section('content')
<h1>{{ $company->name }}</h1>
<p>Owner: {{ optional($company->owner)->name }} ({{ optional($company->owner)->email }})</p>
<p>Verified: {!! $company->is_verified ? '<span class="badge bg-success">Yes</span>' : '<span class="badge bg-secondary">No</span>' !!}</p>
<p>Suspended: {!! $company->is_suspended ? '<span class="badge bg-danger">Yes</span>' : '<span class="badge bg-secondary">No</span>' !!}</p>

@if($company->verification_note)
    <div class="mb-3">
        <label class="form-label">Note</label>
        <div class="card card-body text-bg-dark">
            {!! nl2br(e($company->verification_note)) !!}
        </div>
    </div>
@endif

<div class="d-flex gap-2">
    @if(! $company->is_verified)
    <form method="POST" action="{{ route('admin.companies.verify', $company) }}">
        @csrf
        <div class="mb-2">
            <textarea name="note" class="form-control" placeholder="Verification note" required></textarea>
        </div>
        <button class="btn btn-success">Verify</button>
    </form>
    @else
    <form method="POST" action="{{ route('admin.companies.unverify', $company) }}">
        @csrf
        <div class="mb-2">
            <textarea name="note" class="form-control" placeholder="Why unverify? (optional)"></textarea>
        </div>
        <button class="btn btn-warning">Unverify</button>
    </form>
    @endif

    @if(! $company->is_suspended)
    <form method="POST" action="{{ route('admin.companies.suspend', $company) }}">
        @csrf
        <div class="mb-2">
            <textarea name="note" class="form-control" placeholder="Suspension note" required></textarea>
        </div>
        <button class="btn btn-danger">Suspend</button>
    </form>
    @else
    <form method="POST" action="{{ route('admin.companies.unsuspend', $company) }}">
        @csrf
        <button class="btn btn-primary">Unsuspend</button>
    </form>
    @endif
</div>

<hr>

<h4 class="mt-4">Jobs by company</h4>
<table class="table table-dark table-striped">
    <thead>
        <tr><th>Title</th><th>Status</th><th>Actions</th></tr>
    </thead>
    <tbody>
        @forelse($company->jobs as $job)
            <tr>
                <td>{{ $job->title }}</td>
                <td>{{ $job->status }}</td>
                <td>
                    <a class="btn btn-sm btn-outline-light" href="{{ route('admin.jobs.edit', $job) }}">Edit</a>
                    <form method="POST" action="{{ route('admin.jobs.destroy', $job) }}" class="d-inline" onsubmit="return confirm('Delete this job?');">
                        @csrf @method('DELETE')
                        <button class="btn btn-sm btn-danger">Delete</button>
                    </form>
                </td>
            </tr>
        @empty
            <tr><td colspan="3">No jobs</td></tr>
        @endforelse
    </tbody>
</table>

@endsection

