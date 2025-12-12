@extends('layouts.app')

@section('content')
<div class="container py-4">

  {{-- HEADER (mobile-first stack) --}}
  <div class="row g-2 mb-3">
    <div class="col-12">
      <h2 class="h5 h4-md text-light mb-1">
        Ringkasan AI Pelamar
      </h2>
      <div class="text-light small">
        <strong>{{ $job->title }}</strong>
      </div>
    </div>
  </div>

  <hr class="border-secondary">

  {{-- SUMMARY TEXT --}}
  <div class="mb-4 text-light">
    {{ $summary['summary_text'] }}
  </div>

  @if($summary['total'] > 0)

    <h4 class="h6 h5-md text-light mb-3">Top 3 Kandidat</h4>

    {{-- ================= --}}
    {{-- MOBILE: CARDS    --}}
    {{-- ================= --}}
    <div class="d-md-none">
      @foreach($summary['top3'] as $i => $app)
        <div class="card bg-dark text-light border-secondary mb-3">
          <div class="card-body">

            <div class="d-flex justify-content-between align-items-start mb-1">
              <div>
                <div class="fw-semibold">
                  #{{ $i + 1 }} · {{ $app->user->name }}
                </div>
                <div class="small">
                  {{ $app->user->email }}
                </div>
              </div>

              <span class="badge bg-primary">
                {{ number_format($app->ai_score, 1) }}
              </span>
            </div>

            <div class="small bg-secondary bg-opacity-25 p-2 rounded">
              {{ $app->ai_notes }}
            </div>

          </div>
        </div>
      @endforeach
    </div>

    {{-- ================= --}}
    {{-- DESKTOP: TABLE   --}}
    {{-- ================= --}}
    <div class="table-responsive d-none d-md-block">
      <table class="table table-dark table-hover align-middle">
        <thead>
          <tr>
            <th>#</th>
            <th>Nama</th>
            <th>Email</th>
            <th>AI Score</th>
            <th>Catatan AI</th>
          </tr>
        </thead>
        <tbody>
          @foreach($summary['top3'] as $i => $app)
            <tr>
              <td>{{ $i + 1 }}</td>
              <td>{{ $app->user->name }}</td>
              <td>{{ $app->user->email }}</td>
              <td>
                <span class="badge bg-primary">
                  {{ number_format($app->ai_score, 1) }}
                </span>
              </td>
              <td style="white-space: pre-wrap;">
                {{ $app->ai_notes }}
              </td>
            </tr>
          @endforeach
        </tbody>
      </table>
    </div>

  @else
    <p class="text-light">
      Belum ada pelamar yang dapat diringkas.
    </p>
  @endif

</div>
@endsection

