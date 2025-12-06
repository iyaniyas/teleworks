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
    <div class="card">
      <div class="card-body text-muted">
        Belum ada lowongan. <a href="{{ route('employer.jobs.create') }}">Buat sekarang</a>
      </div>
    </div>
  @else
    @php
      $now = \Carbon\Carbon::now();

      $activeJobs = $jobs->filter(function ($job) use ($now) {
          $expiresAt = null;
          if (!empty($job->expires_at)) {
              $expiresAt = $job->expires_at instanceof \Carbon\Carbon
                  ? $job->expires_at
                  : \Carbon\Carbon::parse($job->expires_at);
          }
          return $job->status === 'published'
              && $expiresAt
              && $expiresAt->gt($now);
      });

      $inactiveJobs = $jobs->filter(function ($job) use ($activeJobs) {
          return ! $activeJobs->contains('id', $job->id);
      });
    @endphp

    {{-- LOWONGAN AKTIF --}}
    @if($activeJobs->count() > 0)
      <h5 class="mb-2">Lowongan Aktif</h5>
      <div class="table-responsive mb-4">
        <table class="table table-striped align-middle">
          <thead>
            <tr>
              <th>Judul</th>
              <th>Status</th>
              <th>Remote</th>
              <th>Pelamar</th>
              <th>Aktif s/d</th>
              <th class="text-end"></th>
            </tr>
          </thead>
          <tbody>
            @foreach($activeJobs as $job)
              <tr>
                <td>
                  <strong>{{ $job->title }}</strong><br>
                  <small class="text-muted">
                    {{ \Illuminate\Support\Str::limit(strip_tags($job->description ?? ''), 100) }}
                  </small>
                </td>
                <td>
                  <span class="badge bg-success">published</span>
                </td>
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
                <td>
                  @if($job->expires_at)
                    {{ $job->expires_at instanceof \Carbon\Carbon
                        ? $job->expires_at->format('d M Y')
                        : \Carbon\Carbon::parse($job->expires_at)->format('d M Y') }}
                  @else
                    -
                  @endif
                </td>
                <td class="text-end">
                  @if($job->is_imported)
                    <span class="badge bg-warning text-dark">Imported</span>
                    <a href="{{ route('jobs.show', $job->id) }}" class="btn btn-sm btn-outline-secondary">Lihat</a>
                  @else
                    <a href="{{ route('employer.jobs.edit', $job->id) }}" class="btn btn-sm btn-outline-primary">Edit</a>
                    <a href="{{ route('jobs.show', $job->id) }}" class="btn btn-sm btn-outline-secondary">Lihat Publik</a>
                    <form action="{{ route('employer.jobs.destroy', $job->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Hapus lowongan?');">
                      @csrf
                      @method('DELETE')
                      <button class="btn btn-sm btn-danger">Hapus</button>
                    </form>
                    @php
                      $paidUntil = null;
                      if (!empty($job->paid_until)) {
                          try { $paidUntil = \Carbon\Carbon::parse($job->paid_until); } catch (\Exception $e) { $paidUntil = null; }
                      } elseif (!empty($job->expires_at)) {
                          $paidUntil = $job->expires_at instanceof \Carbon\Carbon
                              ? $job->expires_at
                              : \Carbon\Carbon::parse($job->expires_at);
                      }
                    @endphp
                    @if($paidUntil)
                      <div><small class="text-muted">Paket aktif s/d {{ $paidUntil->format('d M Y') }}</small></div>
                    @endif
                  @endif
                </td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>
    @endif

    {{-- LOWONGAN DRAFT / NON-AKTIF --}}
    @if($inactiveJobs->count() > 0)
      <h5 class="mb-2">Lowongan Draft / Non-aktif</h5>
      <div class="table-responsive">
        <table class="table table-striped align-middle">
          <thead>
            <tr>
              <th>Judul</th>
              <th>Status</th>
              <th>Remote</th>
              <th>Pelamar</th>
              <th>Expires</th>
              <th class="text-end"></th>
            </tr>
          </thead>
          <tbody>
            @foreach($inactiveJobs as $job)
              @php
                $now = \Carbon\Carbon::now();
                $paidUntil = null;

                if (!empty($job->paid_until)) {
                    try { $paidUntil = \Carbon\Carbon::parse($job->paid_until); } catch (\Exception $e) { $paidUntil = null; }
                } elseif (!empty($job->expires_at)) {
                    $paidUntil = $job->expires_at instanceof \Carbon\Carbon
                        ? $job->expires_at
                        : \Carbon\Carbon::parse($job->expires_at);
                }

                $isPaidActive = $paidUntil
                    && $paidUntil->gt($now)
                    && (int)($job->is_paid ?? 0) === 1;
              @endphp
              <tr>
                <td>
                  <strong>{{ $job->title }}</strong><br>
                  <small class="text-muted">
                    {{ \Illuminate\Support\Str::limit(strip_tags($job->description ?? ''), 100) }}
                  </small>
                  @if($isPaidActive && $job->status !== 'published')
                    <br><small class="text-success">Paket sudah dibayar, siap dipublish.</small>
                  @elseif(!$isPaidActive && (int)($job->is_paid ?? 0) === 1)
                    <br><small class="text-warning">Paket sudah habis masa aktifnya.</small>
                  @endif
                </td>
                <td>
                  <span class="badge bg-{{ $job->status === 'published' ? 'success' : 'secondary' }}">
                    {{ $job->status }}
                  </span>
                </td>
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
                <td>
                  @if($job->expires_at)
                    {{ $job->expires_at instanceof \Carbon\Carbon
                        ? $job->expires_at->format('d M Y')
                        : \Carbon\Carbon::parse($job->expires_at)->format('d M Y') }}
                  @else
                    -
                  @endif
                </td>
                <td class="text-end">
                  @if($job->is_imported)
                    <span class="badge bg-warning text-dark">Imported</span>
                    <a href="{{ route('jobs.show', $job->id) }}" class="btn btn-sm btn-outline-secondary">Lihat</a>
                  @else
                    <a href="{{ route('employer.jobs.edit', $job->id) }}" class="btn btn-sm btn-outline-primary">Edit</a>

                    @if($job->status === 'published')
                      <a href="{{ route('jobs.show', $job->id) }}" class="btn btn-sm btn-outline-secondary">Lihat Publik</a>
                    @endif

                    {{-- AKSI BILLING / PUBLISH --}}
                    @if(!$isPaidActive && $job->status !== 'published')
                      {{-- Belum ada paket aktif untuk job ini --}}
                      <a href="{{ route('purchase.create', ['job_id' => $job->id]) }}"
                         class="btn btn-sm btn-outline-success">
                        Beli Paket
                      </a>
                    @elseif($isPaidActive && $job->status !== 'published')
                      {{-- Sudah dibayar tapi masih draft -> boleh publish --}}
                      <form action="{{ route('employer.jobs.publish', $job) }}"
                            method="POST"
                            class="d-inline">
                        @csrf
                        <button class="btn btn-sm btn-success">
                          Publish
                        </button>
                      </form>
                    @endif

                    <form action="{{ route('employer.jobs.destroy', $job->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Hapus lowongan?');">
                      @csrf
                      @method('DELETE')
                      <button class="btn btn-sm btn-danger">Hapus</button>
                    </form>
                  @endif
                </td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>
    @endif

    {{-- Pagination untuk semua job (active + inactive) --}}
    <div class="mt-3">
      {{ $jobs->links() }}
    </div>
  @endif
</div>
@endsection

