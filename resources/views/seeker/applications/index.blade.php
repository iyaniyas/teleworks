@extends('layouts.app')

@section('content')
<div class="container" style="padding:28px;">
  <h2 style="color:#e6eef8">Lamaran Saya</h2>
  <p style="color:#9fb0c8">Lihat status lamaran dan aksi yang tersedia.</p>

  <div style="margin-top:12px;">
    @foreach($applications as $app)
      <div style="background:rgba(255,255,255,0.02);padding:12px;border-radius:10px;margin-bottom:10px;display:flex;justify-content:space-between;align-items:center;">
        <div>
          <a href="{{ route('jobs.show', $app->job->id) }}" style="font-weight:700;color:#e6eef8">{{ $app->job->title }}</a>
          <div style="color:#9fb0c8;font-size:13px">{{ $app->job->hiring_organization ?? $app->job->company }} • {{ $app->job->job_location ?? $app->job->location }}</div>
          <div style="margin-top:6px;color:#9fb0c8;font-size:13px">Status: <strong style="color:#e6eef8">{{ ucfirst($app->status) }}</strong></div>
        </div>

        <div style="display:flex;gap:8px;align-items:center;">
          @if($app->resume_path)
            <a href="{{ route('employer.applications.resume', $app->id) }}" class="btn btn-outline">Download CV</a>
          @endif

          <form method="POST" action="{{ route('seeker.applications.withdraw', $app->id) }}">
            @csrf
            <button class="btn btn-outline" type="submit" onclick="return confirm('Yakin ingin menarik lamaran?')">Withdraw</button>
          </form>
        </div>
      </div>
    @endforeach

    {{ $applications->links() }}
  </div>
</div>
@endsection

