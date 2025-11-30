@extends('layouts.app')

@section('content')
<div class="container py-5" style="background:#14161c; min-height:100vh;">
  <div class="row justify-content-center">
    <div class="col-lg-10">

      <div class="mb-4">
        <h3 style="font-weight:600;color:#e8eaf1;">Lamaran Saya</h3>
        <div style="color:#9da3b4;">
          Lihat status lamaran dan kelola aplikasi yang sudah kamu kirim.
        </div>
      </div>

      <div class="card border-0 shadow-sm" style="background:#1c1f2a;color:#d1d6e3;">
        <div class="card-body">

          @if($applications->count())

            @foreach($applications as $app)
              @php
                  $job = $app->job ?? null;
              @endphp

              <div class="mb-3 p-3 rounded"
                   style="background:#181b25;border:1px solid #252943;">
                <div class="d-flex justify-content-between align-items-start gap-3">
                  <div>
                    @if($job)
                      <a href="{{ route('jobs.show', $job->id) }}"
                         style="color:#f1f3ff;text-decoration:none;font-weight:600;">
                        {{ $job->title }}
                      </a>
                      <div class="small" style="color:#b2b7ce;">
                        {{ $job->hiring_organization ?? $job->company ?? 'Perusahaan' }}
                        · {{ $job->job_location ?? $job->location ?? 'Lokasi tidak tercantum' }}
                      </div>
                    @else
                      <div style="color:#f1f3ff;font-weight:600;">
                        (Lowongan tidak tersedia)
                      </div>
                    @endif

                    <div class="small mt-2" style="color:#9da3b4;">
                      Tanggal lamar:
                      <span style="color:#dfe3ff;">
                        {{ optional($app->created_at)->format('d M Y') }}
                      </span>
                    </div>

                    <div class="small mt-1" style="color:#9da3b4;">
                      Status:
                      <span class="badge rounded-pill"
                            style="background:#2a2f45;color:#d5d9ff;">
                        {{ ucfirst($app->status) }}
                      </span>
                    </div>
                  </div>

                  <div class="d-flex flex-column align-items-end gap-2">

                    @if($app->resume_path)
                      <a href="{{ route('employer.applications.resume', $app->id) }}"
                         class="btn btn-sm btn-outline-light"
                         style="border-color:#3a3f58;color:#dce1f1;">
                        Download CV
                      </a>
                    @endif

                    <form method="POST" action="{{ route('seeker.applications.withdraw', $app->id) }}">
                      @csrf
                      <button class="btn btn-sm btn-outline-danger"
                              onclick="return confirm('Yakin ingin menarik lamaran?')">
                        Withdraw
                      </button>
                    </form>
                  </div>
                </div>
              </div>
            @endforeach

            {{-- PAGINATION --}}
            <div class="mt-3">
              {{ $applications->links() }}
            </div>

          @else

            <div class="py-5 text-center">
              <div class="mb-2" style="font-weight:500;color:#e2e5f4;">
                Belum ada lamaran tercatat.
              </div>
              <div class="small mb-3" style="color:#9da3b4;">
                Cari lowongan dan kirim lamaran pertamamu di Teleworks.id.
              </div>
              <a href="{{ url('/cari') }}" class="btn btn-primary btn-sm">
                Cari Lowongan
              </a>
            </div>

          @endif

        </div>
      </div>

    </div>
  </div>
</div>
@endsection

