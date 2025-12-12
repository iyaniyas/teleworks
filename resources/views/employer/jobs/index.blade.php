{{-- resources/views/employer/jobs/index.blade.php --}}
@extends('layouts.app')

@section('title', 'Kelola Lowongan')

@section('content')
<div class="container py-4">

  {{-- HEADER --}}
  <div class="row g-2 align-items-center mb-3">
    <div class="col-12 col-md">
      <h3 class="text-light mb-0">Lowongan Saya</h3>
    </div>
    <div class="col-12 col-md-auto">
      <a href="{{ route('employer.jobs.create') }}"
         class="btn btn-primary w-100 w-md-auto">
        + Buat Lowongan
      </a>
    </div>
  </div>

  @if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
  @endif
  @if(session('error'))
    <div class="alert alert-danger">{{ session('error') }}</div>
  @endif

  @if($jobs->isEmpty())
    <div class="card bg-dark border-secondary">
      <div class="card-body text-light">
        Belum ada lowongan.
        <a href="{{ route('employer.jobs.create') }}" class="text-decoration-underline">
          Buat sekarang
        </a>
      </div>
    </div>
  @else

    @php
      $now = \Carbon\Carbon::now();
    @endphp

    {{-- ========================= --}}
    {{-- MOBILE FIRST (CARD)     --}}
    {{-- ========================= --}}
    <div class="d-md-none">
      @foreach($jobs as $job)
        @php
          $expiresAt = $job->expires_at
              ? ($job->expires_at instanceof \Carbon\Carbon
                  ? $job->expires_at
                  : \Carbon\Carbon::parse($job->expires_at))
              : null;

          $paidUntil = null;
          if (!empty($job->paid_until)) {
              try { $paidUntil = \Carbon\Carbon::parse($job->paid_until); } catch (\Exception $e) {}
          } elseif ($expiresAt) {
              $paidUntil = $expiresAt;
          }

          $isPaidActive = $paidUntil
              && $paidUntil->gt($now)
              && (int)($job->is_paid ?? 0) === 1;

          $appCount = 0;
          if (class_exists(\App\Models\JobApplication::class)) {
            $appCount = \App\Models\JobApplication::where('job_id', $job->id)->count();
          } elseif (class_exists(\App\Models\Application::class)) {
            $appCount = \App\Models\Application::where('job_id', $job->id)->count();
          }
        @endphp

        <div class="card bg-dark text-light border-secondary mb-3">
          <div class="card-body">

            <div class="d-flex justify-content-between align-items-start">
              <div>
                <div class="fw-semibold">{{ $job->title }}</div>
                <div class="small">
                  {{ \Illuminate\Support\Str::limit(strip_tags($job->description ?? ''), 100) }}
                </div>
              </div>
              <span class="badge bg-{{ $job->status === 'published' ? 'success' : 'secondary' }}">
                {{ $job->status }}
              </span>
            </div>

            <div class="mt-2 small">
              Pelamar: <strong>{{ $appCount }}</strong><br>
              @if($expiresAt)
                Aktif s/d {{ $expiresAt->format('d M Y') }}
              @endif
            </div>

            @if($isPaidActive && $job->status !== 'published')
              <div class="mt-2 text-success small">
                Paket sudah dibayar, siap dipublish.
              </div>
            @elseif(!$isPaidActive && (int)($job->is_paid ?? 0) === 1)
              <div class="mt-2 text-warning small">
                Paket sudah habis masa aktifnya.
              </div>
            @endif

            {{-- AKSI (FULL, TIDAK DIPOTONG) --}}
            <div class="mt-3 d-flex flex-wrap gap-2">

              @if(!$job->is_imported)
                <a href="{{ route('employer.jobs.edit', $job->id) }}"
                   class="btn btn-sm btn-outline-primary">
                  Edit
                </a>
              @endif

              <a href="{{ route('jobs.show', $job->id) }}"
                 class="btn btn-sm btn-outline-secondary">
                Lihat Publik
              </a>

              @if($job->status === 'published')
                <a href="/employer/jobs/{{ $job->id }}/applicants"
                   class="btn btn-sm btn-outline-light">
                  Pelamar
                </a>
              @endif

              {{-- AKSI BILLING / PUBLISH --}}
              @if(!$job->is_imported)
                @if(!$isPaidActive && $job->status !== 'published')
                  <a href="{{ route('purchase.create', ['job_id' => $job->id]) }}"
                     class="btn btn-sm btn-outline-success">
                    Beli Paket
                  </a>
                @elseif($isPaidActive && $job->status !== 'published')
                  <form action="{{ route('employer.jobs.publish', $job) }}"
                        method="POST">
                    @csrf
                    <button class="btn btn-sm btn-success">
                      Publish
                    </button>
                  </form>
                @endif
              @endif

              @if(!$job->is_imported)
                <form action="{{ route('employer.jobs.destroy', $job->id) }}"
                      method="POST"
                      onsubmit="return confirm('Hapus lowongan?');">
                  @csrf
                  @method('DELETE')
                  <button class="btn btn-sm btn-danger">
                    Hapus
                  </button>
                </form>
              @endif

            </div>

          </div>
        </div>
      @endforeach
    </div>

    {{-- ========================= --}}
    {{-- DESKTOP TABLE           --}}
    {{-- ========================= --}}
    <div class="table-responsive d-none d-md-block">
      <table class="table table-dark table-hover align-middle">
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
          @foreach($jobs as $job)
            @php
              $expiresAt = $job->expires_at
                  ? ($job->expires_at instanceof \Carbon\Carbon
                      ? $job->expires_at
                      : \Carbon\Carbon::parse($job->expires_at))
                  : null;

              $paidUntil = null;
              if (!empty($job->paid_until)) {
                  try { $paidUntil = \Carbon\Carbon::parse($job->paid_until); } catch (\Exception $e) {}
              } elseif ($expiresAt) {
                  $paidUntil = $expiresAt;
              }

              $isPaidActive = $paidUntil
                  && $paidUntil->gt($now)
                  && (int)($job->is_paid ?? 0) === 1;

              $appCount = 0;
              if (class_exists(\App\Models\JobApplication::class)) {
                $appCount = \App\Models\JobApplication::where('job_id', $job->id)->count();
              } elseif (class_exists(\App\Models\Application::class)) {
                $appCount = \App\Models\Application::where('job_id', $job->id)->count();
              }
            @endphp

            <tr>
              <td>
                <strong>{{ $job->title }}</strong><br>
                <small>{{ \Illuminate\Support\Str::limit(strip_tags($job->description ?? ''), 100) }}</small>
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

              <td>{{ $appCount }}</td>

              <td>{{ $expiresAt ? $expiresAt->format('d M Y') : '-' }}</td>

              <td class="text-end">

                @if(!$job->is_imported)
                  <a href="{{ route('employer.jobs.edit', $job->id) }}"
                     class="btn btn-sm btn-outline-primary">
                    Edit
                  </a>
                @endif

                <a href="{{ route('jobs.show', $job->id) }}"
                   class="btn btn-sm btn-outline-secondary">
                  Lihat Publik
                </a>

                @if($job->status === 'published')
                  <a href="/employer/jobs/{{ $job->id }}/applicants"
                     class="btn btn-sm btn-outline-light">
                    Pelamar
                  </a>
                @endif

                {{-- AKSI BILLING / PUBLISH (DESKTOP JUGA WAJIB) --}}
                @if(!$job->is_imported)
                  @if(!$isPaidActive && $job->status !== 'published')
                    <a href="{{ route('purchase.create', ['job_id' => $job->id]) }}"
                       class="btn btn-sm btn-outline-success">
                      Beli Paket
                    </a>
                  @elseif($isPaidActive && $job->status !== 'published')
                    <form action="{{ route('employer.jobs.publish', $job) }}"
                          method="POST"
                          class="d-inline">
                      @csrf
                      <button class="btn btn-sm btn-success">
                        Publish
                      </button>
                    </form>
                  @endif
                @endif

                @if(!$job->is_imported)
                  <form action="{{ route('employer.jobs.destroy', $job->id) }}"
                        method="POST"
                        class="d-inline"
                        onsubmit="return confirm('Hapus lowongan?');">
                    @csrf
                    @method('DELETE')
                    <button class="btn btn-sm btn-danger">
                      Hapus
                    </button>
                  </form>
                @endif

              </td>
            </tr>
          @endforeach
        </tbody>
      </table>
    </div>

    <div class="mt-3">
      {{ $jobs->links() }}
    </div>

  @endif
</div>
@endsection

