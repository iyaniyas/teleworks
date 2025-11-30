@extends('layouts.app')

@section('content')
<div class="container py-5" style="background:#14161c; min-height:100vh;">
  <div class="row justify-content-center">
    <div class="col-lg-10">

      <div class="mb-4">
        <h3 style="font-weight:600;color:#e8eaf1;">Lowongan Tersimpan</h3>
        <div style="color:#9da3b4;">
          Lowongan yang kamu simpan untuk dilihat atau dilamar nanti.
        </div>
      </div>

      <div class="card border-0 shadow-sm" style="background:#1c1f2a;color:#d1d6e3;">
        <div class="card-body">

          @if($saved->count())

            @foreach($saved as $item)
              @php
                  $job = $item->job ?? null;
              @endphp

              <div class="mb-3 p-3 rounded d-flex justify-content-between align-items-start gap-3"
                   style="background:#181b25;border:1px solid #252943;">

                <div>
                  @if($job)
                    <a href="{{ route('jobs.show', $job->id) }}"
                       style="color:#f1f3ff;text-decoration:none;font-weight:600;">
                      {{ $job->title }}
                    </a>
                    <div class="small" style="color:#b2b7ce;">
                      {{ $job->hiring_organization ?? $job->company ?? 'Perusahaan' }}
                    </div>
                  @else
                    <div style="color:#f1f3ff;font-weight:600;">
                      (Lowongan tidak tersedia)
                    </div>
                  @endif
                </div>

                @if($job)
                  <form method="POST" action="{{ route('jobs.bookmark', $job->id) }}">
                    @csrf
                    <button class="btn btn-sm btn-outline-light"
                            style="border-color:#3a3f58;color:#dce1f1;">
                      Hapus
                    </button>
                  </form>
                @endif

              </div>
            @endforeach

          @else

            <div class="py-5 text-center">
              <div class="mb-2" style="font-weight:500;color:#e2e5f4;">
                Belum ada lowongan yang kamu simpan.
              </div>
              <div class="small mb-3" style="color:#9da3b4;">
                Simpan lowongan yang menarik agar mudah ditemukan kembali.
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

