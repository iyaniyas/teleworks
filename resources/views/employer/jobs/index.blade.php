{{-- resources/views/employer/jobs/index.blade.php --}}
@extends('layouts.app')

@section('title', 'Kelola Lowongan')

@section('content')
<div class="container py-4">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h3>Lowongan Saya</h3>
    <a href="{{ route('employer.jobs.create') }}" class="btn btn-primary">+ Buat Lowongan</a>
  </div>

  @if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
  @endif
  @if(session('error'))
    <div class="alert alert-danger">{{ session('error') }}</div>
  @endif

  @if($jobs->isEmpty())
    <div class="card"><div class="card-body text-muted">Belum ada lowongan. <a href="{{ route('employer.jobs.create') }}">Buat sekarang</a></div></div>
  @else
    <div class="table-responsive">
      <table class="table table-striped align-middle">
        <thead>
          <tr>
            <th>Judul</th>
            <th>Status</th>
            <th>Remote</th>
            <th>Pelamar</th>
            <th>Expires</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          @foreach($jobs as $job)
            <tr>
              <td><strong>{{ $job->title }}</strong><br><small class="text-muted">{{ \Illuminate\Support\Str::limit(strip_tags($job->description ?? ''), 100) }}</small></td>
              <td><span class="badge bg-{{ $job->status == 'published' ? 'success' : 'secondary' }}">{{ $job->status }}</span></td>
              <td>
                @if($job->is_remote)
                  <span class="badge bg-info text-dark">Remote</span>
                @elseif($job->is_wfh)
                  <span class="badge bg-info text-dark">WFH</span>
                @else
                  -
                @endif
              </td>
              <td>
                @php
                  $appCount = 0;
                  if (class_exists(\App\Models\JobApplication::class)) {
                    $appCount = \App\Models\JobApplication::where('job_id', $job->id)->count();
                  } elseif (class_exists(\App\Models\Application::class)) {
                    $appCount = \App\Models\Application::where('job_id', $job->id)->count();
                  }
                @endphp
                {{ $appCount }}
              </td>
              <td>{{ $job->expires_at ? \Carbon\Carbon::parse($job->expires_at)->format('d M Y') : '-' }}</td>
              <td class="text-end">
                @if($job->is_imported)
                  <span class="badge bg-warning text-dark">Imported</span>
                  <a href="{{ route('jobs.show', $job->id) }}" class="btn btn-sm btn-outline-secondary">Lihat</a>
                @else
                  <a href="{{ route('employer.jobs.edit', $job->id) }}" class="btn btn-sm btn-outline-primary">Edit</a>
                  <a href="{{ route('jobs.show', $job->id) }}" class="btn btn-sm btn-outline-secondary">Lihat Publik</a>
                  <form action="{{ route('employer.jobs.destroy', $job->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Hapus lowongan?');">
                    @csrf @method('DELETE')
                    <button class="btn btn-sm btn-danger">Hapus</button>
                  </form>
                @endif
              </td>
            </tr>
          @endforeach
        </tbody>
      </table>
    </div>

    {{ $jobs->links() }}
  @endif
</div>
@endsection

