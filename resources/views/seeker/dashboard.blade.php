@extends('layouts.app')

@section('content')
<div class="container" style="padding:28px;">
  <div style="display:flex;gap:20px;align-items:center;justify-content:space-between;">
    <div>
      <h2 style="color:#e6eef8;margin:0">Halo, {{ auth()->user()->name }}</h2>
      <p style="color:#9fb0c8;margin:6px 0 0">Ringkasan aktivitas terbaru</p>
    </div>
    <div style="display:flex;gap:12px;align-items:center;">
      <a href="{{ route('seeker.profile.edit') }}" class="btn btn-outline">Edit Profil</a>
      <form method="POST" action="{{ route('logout') }}">
        @csrf
        <button class="btn btn-outline" type="submit">Logout</button>
      </form>
    </div>
  </div>

  <div style="display:grid;grid-template-columns:1fr 360px;gap:20px;margin-top:18px;">
    <!-- Main -->
    <div>
      <!-- Stats -->
      <div style="display:flex;gap:12px;margin-bottom:18px;">
        <div style="flex:1;background:rgba(255,255,255,0.02);padding:14px;border-radius:10px;">
          <div style="font-size:13px;color:#9fb0c8">Lamaran</div>
          <div style="font-size:20px;color:#e6eef8;font-weight:700">{{ \App\Models\JobApplication::where('user_id', auth()->id())->count() }}</div>
        </div>
        <div style="flex:1;background:rgba(255,255,255,0.02);padding:14px;border-radius:10px;">
          <div style="font-size:13px;color:#9fb0c8">Lowongan Tersimpan</div>
          <div style="font-size:20px;color:#e6eef8;font-weight:700">{{ \App\Models\Bookmark::where('user_id', auth()->id())->count() ?? 0 }}</div>
        </div>
        <div style="flex:1;background:rgba(255,255,255,0.02);padding:14px;border-radius:10px;">
          <div style="font-size:13px;color:#9fb0c8">Notifikasi</div>
          <div style="font-size:20px;color:#e6eef8;font-weight:700">{{ auth()->user()->unreadNotifications()->count() }}</div>
        </div>
      </div>

      <!-- Recommended jobs (simple) -->
      <h3 style="color:#e6eef8;margin-top:6px">Rekomendasi Untukmu</h3>
      <div style="display:flex;flex-direction:column;gap:12px;margin-top:10px;">
        @foreach($recommendedJobs ?? \App\Models\Job::latest()->take(6)->get() as $job)
        <div style="background:rgba(255,255,255,0.02);padding:12px;border-radius:10px;display:flex;justify-content:space-between;align-items:center;">
          <div>
            <a href="{{ route('jobs.show', $job->id) }}" style="color:#e6eef8;font-weight:700">{{ $job->title }}</a>
            <div style="color:#9fb0c8;font-size:13px">{{ $job->hiring_organization ?? $job->company }} • {{ $job->job_location ?? $job->location }}</div>
          </div>
          <div style="display:flex;gap:8px;align-items:center;">
            <form method="POST" action="{{ route('jobs.bookmark', $job->id) }}">@csrf
              <button class="btn btn-outline" title="Simpan">Simpan</button>
            </form>
            <a href="{{ route('jobs.show', $job->id) }}" class="btn btn-primary">Lihat</a>
          </div>
        </div>
        @endforeach
      </div>
    </div>

    <!-- Right column: profile card -->
    <div>
      <div style="background:rgba(255,255,255,0.02);padding:14px;border-radius:10px;">
        <div style="display:flex;gap:12px;align-items:center">
          <div style="width:64px;height:64px;border-radius:10px;background:rgba(255,255,255,0.03);display:flex;align-items:center;justify-content:center;">
            {{ strtoupper(substr(auth()->user()->name,0,1)) }}
          </div>
          <div>
            <div style="font-weight:700;color:#e6eef8">{{ auth()->user()->name }}</div>
            <div style="color:#9fb0c8;font-size:13px">{{ auth()->user()->profile->headline ?? 'Belum mengisi headline' }}</div>
          </div>
        </div>

        <div style="margin-top:12px;color:#9fb0c8;font-size:13px">
          <div>Lokasi: {{ auth()->user()->profile->location ?? '-' }}</div>
          <div style="margin-top:8px">Skills:
            @if(auth()->user()->profile && auth()->user()->profile->skills)
              @foreach(json_decode(auth()->user()->profile->skills) as $skill)
                <span style="background:rgba(255,255,255,0.03);padding:4px 8px;border-radius:6px;margin-left:6px;font-size:12px;color:#e6eef8">{{ $skill }}</span>
              @endforeach
            @else
              <span style="color:#6b7280">Belum ada</span>
            @endif
          </div>
        </div>

        <div style="margin-top:12px;display:flex;gap:8px;">
          <a href="{{ route('seeker.profile.edit') }}" class="btn btn-primary" style="flex:1">Lengkapi Profil</a>
          <a href="{{ route('seeker.applications.index') }}" class="btn btn-outline">Lamaran Saya</a>
        </div>
      </div>
    </div>
  </div>
</div>
@endsection

