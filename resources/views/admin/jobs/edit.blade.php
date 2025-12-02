@extends('admin.layout')

@section('title','Edit Job')

@section('content')
<h1>Edit Job: {{ $job->title }}</h1>

<form method="POST" action="{{ route('admin.jobs.update', $job) }}">
    @csrf @method('PUT')

    <div class="mb-3">
        <label class="form-label">Title</label>
        <input name="title" class="form-control" value="{{ old('title', $job->title) }}" required>
    </div>

    <div class="mb-3">
        <label class="form-label">Description</label>
        <textarea name="description" class="form-control" rows="6">{{ old('description', $job->description) }}</textarea>
    </div>

    <div class="mb-3">
        <label class="form-label">Status</label>
        <select name="status" class="form-select" required>
            @foreach(['draft','pending','approved','rejected','paused'] as $s)
                <option value="{{ $s }}" @selected(old('status', $job->status) === $s)>{{ ucfirst($s) }}</option>
            @endforeach
        </select>
    </div>

    <button class="btn btn-primary">Save</button>
    <a class="btn btn-outline-light" href="{{ route('admin.jobs.index') }}">Back</a>
</form>
@endsection

