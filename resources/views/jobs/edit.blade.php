@extends('layouts.app')

@section('content')
<div class="container">
  <h1>Edit Job</h1>

  @if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
  @endif

  <form action="{{ route('jobs.update', $job->id) }}" method="POST">
    @csrf
    @method('PATCH')

    <div class="mb-3">
      <label class="form-label">Title</label>
      <input type="text" name="title" value="{{ old('title', $job->title) }}" class="form-control">
      @error('title')<div class="text-danger">{{ $message }}</div>@enderror
    </div>

    <div class="mb-3">
      <label class="form-label">Description</label>
      <textarea name="description" class="form-control" rows="8">{{ old('description', $job->description) }}</textarea>
      @error('description')<div class="text-danger">{{ $message }}</div>@enderror
    </div>

    <div class="mb-3">
      <label class="form-label">Location</label>
      <input type="text" name="job_location" value="{{ old('job_location', $job->job_location) }}" class="form-control">
    </div>

    <div class="mb-3 row">
      <div class="col">
        <label class="form-label">Base salary min</label>
        <input type="text" name="base_salary_min" value="{{ old('base_salary_min', $job->base_salary_min) }}" class="form-control">
      </div>
      <div class="col">
        <label class="form-label">Base salary max</label>
        <input type="text" name="base_salary_max" value="{{ old('base_salary_max', $job->base_salary_max) }}" class="form-control">
      </div>
    </div>

    <button class="btn btn-primary mt-3">Update Job</button>
  </form>
</div>
@endsection

