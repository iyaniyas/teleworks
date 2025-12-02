@extends('admin.layout')

@section('title','Jobs')

@section('content')
<h1 class="mb-4">Jobs</h1>

<form class="row g-2 mb-3" method="GET">
  <div class="col-auto">
    <input name="q" class="form-control" placeholder="Search jobs" value="{{ $q ?? '' }}">
  </div>
  <div class="col-auto">
    <select name="status" class="form-select">
      <option value="">All statuses</option>
      <option value="pending" @selected(($status ?? '') === 'pending')>Pending</option>
      <option value="approved" @selected(($status ?? '') === 'approved')>Approved</option>
      <option value="rejected" @selected(($status ?? '') === 'rejected')>Rejected</option>
      <option value="paused" @selected(($status ?? '') === 'paused')>Paused</option>
      <option value="draft" @selected(($status ?? '') === 'draft')>Draft</option>
    </select>
  </div>
  <div class="col-auto">
    <button class="btn btn-primary">Filter</button>
  </div>
</form>

<table class="table table-dark table-striped">
  <thead>
    <tr><th>Title</th><th>Company</th><th>Status</th><th>Posted</th><th>Actions</th></tr>
  </thead>
  <tbody>
  @foreach($jobs as $job)
    <tr>
      <td>{{ $job->title }}</td>
      <td>{{ optional($job->company)->name }}</td>
      <td>{{ $job->status }}</td>
      <td>{{ $job->created_at->format('Y-m-d') }}</td>
      <td>
        <a class="btn btn-sm btn-outline-light" href="{{ route('admin.jobs.edit', $job) }}">Edit</a>
        <form method="POST" action="{{ route('admin.jobs.destroy', $job) }}" class="d-inline" onsubmit="return confirm('Delete this job?');">
            @csrf @method('DELETE')
            <button class="btn btn-sm btn-danger">Delete</button>
        </form>
      </td>
    </tr>
  @endforeach
  </tbody>
</table>

{{ $jobs->withQueryString()->links() }}
@endsection

