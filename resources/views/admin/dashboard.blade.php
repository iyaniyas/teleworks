@extends('admin.layout')

@section('title','Dashboard')

@section('content')
<h1 class="mb-4">Admin Dashboard</h1>

<div class="row g-3 mb-4">
  <div class="col-md-3">
    <a href="{{ route('admin.companies.index') }}" class="text-decoration-none">
      <div class="card text-bg-dark h-100">
        <div class="card-body">
          <h5 class="card-title">Companies pending</h5>
          <p class="card-text fs-3">{{ $companiesPending }}</p>
          <p class="card-text"><small class="text-muted">View & verify companies</small></p>
        </div>
        <div class="card-footer bg-transparent border-top-0">
          <button class="btn btn-sm btn-outline-light">Open companies</button>
        </div>
      </div>
    </a>
  </div>

  <div class="col-md-3">
    <a href="{{ route('admin.companies.index').'?filter=suspended' }}" class="text-decoration-none">
      <div class="card text-bg-dark h-100">
        <div class="card-body">
          <h5 class="card-title">Suspended companies</h5>
          <p class="card-text fs-3">{{ $suspended }}</p>
          <p class="card-text"><small class="text-muted">Manage suspensions</small></p>
        </div>
        <div class="card-footer bg-transparent border-top-0">
          <button class="btn btn-sm btn-outline-light">Manage</button>
        </div>
      </div>
    </a>
  </div>

  <div class="col-md-3">
    <a href="{{ route('admin.jobs.index') }}" class="text-decoration-none">
      <div class="card text-bg-dark h-100">
        <div class="card-body">
          <h5 class="card-title">Jobs pending</h5>
          <p class="card-text fs-3">{{ $jobsPending }}</p>
          <p class="card-text"><small class="text-muted">Edit or remove jobs</small></p>
        </div>
        <div class="card-footer bg-transparent border-top-0">
          <button class="btn btn-sm btn-outline-light">Open jobs</button>
        </div>
      </div>
    </a>
  </div>

  <div class="col-md-3">
    <a href="{{ route('admin.reports.index') }}" class="text-decoration-none">
      <div class="card text-bg-dark h-100">
        <div class="card-body">
          <h5 class="card-title">Open reports</h5>
          <p class="card-text fs-3">{{ $openReports }}</p>
          <p class="card-text"><small class="text-muted">Review user reports</small></p>
        </div>
        <div class="card-footer bg-transparent border-top-0">
          <button class="btn btn-sm btn-outline-light">Open reports</button>
        </div>
      </div>
    </a>
  </div>
</div>

<div class="mb-4">
  <h4>Quick links</h4>
  <div class="d-flex flex-wrap gap-2">
    <a href="{{ route('admin.companies.index') }}" class="btn btn-sm btn-outline-light">Companies</a>
    <a href="{{ route('admin.jobs.index') }}" class="btn btn-sm btn-outline-light">Jobs</a>
    <a href="{{ route('admin.reports.index') }}" class="btn btn-sm btn-outline-light">Reports</a>
    <a href="{{ url('/') }}" class="btn btn-sm btn-outline-light">Open site</a>
  </div>
</div>

@endsection

