@extends('layouts.app')

@section('content')
<h3 class="text-light">Lamaran Saya</h3>
<p class="muted-light">Lihat status lamaran dan aksi yang tersedia.</p>

<div class="mt-3">
  @foreach($applications as $app)
    <div class="card bg-dark text-light mb-2">
      <div class="card-body d-flex justify-content-between align-items-center">
        <div>
          <a href="{{ route('jobs.show', $app->job->id) }}" class="h6 text-light">{{ $app->job->title }}</a>
          <div class="small muted-light">{{ $app->job->hiring_organization ?? $app->job->company }} • {{ $app->job->job_location ?? $app->job->location }}</div>
          <div class="small muted-light mt-1">Status: <strong class="text-light">{{ ucfirst($app->status) }}</strong></div>
        </div>

        <div class="d-flex gap-2">
          @if($app->resume_path)
            <a href="{{ route('employer.applications.resume', $app->id) }}" class="btn btn-sm btn-outline-light">Download CV</a>
          @endif

          <form method="POST" action="{{ route('seeker.applications.withdraw', $app->id) }}">
            @csrf
            <button class="btn btn-sm btn-outline-light" onclick="return confirm('Yakin ingin menarik lamaran?')">Withdraw</button>
          </form>
        </div>
      </div>
    </div>
  @endforeach

  {{ $applications->links() }}
</div>
@endsection

