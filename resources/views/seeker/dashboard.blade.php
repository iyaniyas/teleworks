@extends('layouts.app')

@section('content')
<div class="row">
  <div class="col-lg-8">
    <div class="d-flex justify-content-between align-items-start mb-3">
      <div>
        <h2 class="mb-0 text-light">Halo, {{ auth()->user()->name }}</h2>
        <div class="muted-light">Ringkasan aktivitas terbaru</div>
      </div>
      <div class="d-flex gap-2">
        <a href="{{ route('seeker.profile.edit') }}" class="btn btn-outline-light">Edit Profil</a>
        <a href="{{ route('seeker.applications.index') }}" class="btn btn-outline-light">Lamaran Saya</a>
      </div>
    </div>

    <div class="row g-3 mb-4">
      <div class="col-md-4">
        <div class="card bg-dark text-light h-100">
          <div class="card-body">
            <div class="text-muted small">Lamaran</div>
            <div class="h4 mb-0">{{ \App\Models\JobApplication::where('user_id', auth()->id())->count() }}</div>
          </div>
        </div>
      </div>
      <div class="col-md-4">
        <div class="card bg-dark text-light h-100">
          <div class="card-body">
            <div class="text-muted small">Lowongan Tersimpan</div>
            <div class="h4 mb-0">{{ \App\Models\Bookmark::where('user_id', auth()->id())->count() ?? 0 }}</div>
          </div>
        </div>
      </div>
      <div class="col-md-4">
        <div class="card bg-dark text-light h-100">
          <div class="card-body">
            <div class="text-muted small">Notifikasi</div>
            @php
              $notificationCount = 0;
              try {
                if (\Illuminate\Support\Facades\Schema::hasTable('notifications')) {
                  $notificationCount = auth()->user()->unreadNotifications()->count();
                }
              } catch (\Throwable $e) { $notificationCount = 0; }
            @endphp
            <div class="h4 mb-0">{{ $notificationCount }}</div>
          </div>
        </div>
      </div>
    </div>

    <h5 class="text-light mb-3">Rekomendasi Untukmu</h5>

    <div class="row row-cols-1 row-cols-md-2 g-3">
      @foreach($recommendedJobs ?? \App\Models\Job::latest()->take(6)->get() as $job)
      <div class="col">
        <div class="card bg-dark text-light h-100">
          <div class="card-body d-flex flex-column">
            <div class="mb-2">
              <a href="{{ route('jobs.show', $job->id) }}" class="h6 text-light mb-0">{{ $job->title }}</a>
              <div class="small muted-light">{{ $job->hiring_organization ?? $job->company }} • {{ $job->job_location ?? $job->location }}</div>
            </div>

            <div class="mt-auto d-flex justify-content-between align-items-center">
              <form method="POST" action="{{ route('jobs.bookmark', $job->id) }}">
                @csrf
                <button class="btn btn-sm btn-outline-light" title="Simpan">Simpan</button>
              </form>
              <a href="{{ route('jobs.show', $job->id) }}" class="btn btn-sm btn-primary">Lihat</a>
            </div>
          </div>
        </div>
      </div>
      @endforeach
    </div>

  </div>

  <div class="col-lg-4">
    <div class="card bg-dark text-light">
      <div class="card-body">
        <div class="d-flex align-items-center gap-3 mb-2">
          <div class="bg-secondary text-white rounded-circle d-flex align-items-center justify-content-center" style="width:64px;height:64px;font-weight:700;">
            {{ strtoupper(substr(auth()->user()->name,0,1)) }}
          </div>
          <div>
            <div class="fw-bold text-light">{{ auth()->user()->name }}</div>
            <div class="small muted-light">{{ auth()->user()->profile->headline ?? 'Belum mengisi headline' }}</div>
          </div>
        </div>

        <div class="small muted-light mb-2">Lokasi: {{ auth()->user()->profile->location ?? '-' }}</div>

        <div class="mb-3">
          <div class="small muted-light mb-1">Skills</div>
          @if(auth()->user()->profile && auth()->user()->profile->skills)
            @foreach(json_decode(auth()->user()->profile->skills) as $skill)
              <span class="badge bg-secondary me-1 mb-1">{{ $skill }}</span>
            @endforeach
          @else
            <div class="small text-muted">Belum ada</div>
          @endif
        </div>

        <div class="d-grid gap-2">
          <a href="{{ route('seeker.profile.edit') }}" class="btn btn-primary">Lengkapi Profil</a>
          <a href="{{ route('seeker.applications.index') }}" class="btn btn-outline-light">Lamaran Saya</a>
        </div>
      </div>
    </div>
  </div>
</div>
@endsection

