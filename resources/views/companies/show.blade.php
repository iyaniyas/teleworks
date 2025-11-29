@extends('layouts.app')

@section('content')
<div class="container">
  <h1>{{ $company->name }}</h1>
  @if($company->domain)<p><a href="{{ $company->domain }}" target="_blank">{{ $company->domain }}</a></p>@endif
  <p>{{ $company->description }}</p>

  <h3>Lowongan dari {{ $company->name }}</h3>
  @if($company->jobs && $company->jobs->count())
    <ul>
      @foreach($company->jobs as $job)
        <li><a href="{{ route('jobs.show', $job->id) }}">{{ $job->title }}</a> — {{ $job->job_location ?? $job->location }}</li>
      @endforeach
    </ul>
  @else
    <p>Belum ada lowongan.</p>
  @endif
</div>
@endsection

