@extends('layouts.app')

@section('content')
<div class="container py-3">

  {{-- HEADER (mobile-first stack) --}}
  <div class="row g-2 align-items-center mb-3">
    <div class="col-12 col-md">
      <h1 class="h5 h4-md text-light mb-0">
        Pelamar — {{ $job->title }}
      </h1>
    </div>
    <div class="col-12 col-md-auto">
      <a href="/employer/jobs/{{ $job->id }}/applicants/ai-summary"
         class="btn btn-outline-primary btn-sm w-100 w-md-auto">
        Ringkasan AI Seluruh Pelamar
      </a>
    </div>
  </div>

  @if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
  @endif

  @if($apps->count())

    {{-- ================= --}}
    {{-- MOBILE: DEFAULT  --}}
    {{-- ================= --}}
    <div class="d-md-none">
      @foreach($apps as $app)
        @php
          $score = $app->ai_score;
          if (!is_null($score)) {
              if ($score >= 75) { $badge='bg-success'; $label='High'; }
              elseif ($score >= 45) { $badge='bg-warning text-dark'; $label='Medium'; }
              else { $badge='bg-danger'; $label='Low'; }
          } else {
              $badge='bg-secondary'; $label='N/A';
          }

          $summary = $app->ai_notes
              ? preg_replace("/\r\n|\r|\n/", ' ', $app->ai_notes)
              : 'Belum dinilai';
        @endphp

        <div class="card bg-dark text-light border-secondary mb-3">
          <div class="card-body">

            <div class="d-flex justify-content-between align-items-start">
              <div>
                <div class="fw-semibold">{{ $app->user->name }}</div>
                <div class="small">{{ $app->user->email }}</div>
              </div>

              <span class="badge {{ $badge }}">
                {{ !is_null($score) ? number_format($score,1) : $label }}
              </span>
            </div>

            <div class="mt-2 small bg-secondary bg-opacity-25 p-2 rounded">
              {{ $summary }}
            </div>

            <div class="mt-2 small text-muted">
              Status: {{ ucfirst($app->status) }} ·
              {{ $app->created_at->format('d M Y') }}
            </div>

          </div>
        </div>
      @endforeach
    </div>

    {{-- ================= --}}
    {{-- DESKTOP: ENHANCE --}}
    {{-- ================= --}}
    <div class="table-responsive d-none d-md-block">
      <table class="table table-dark table-hover align-middle">
        <thead>
          <tr>
            <th>#</th>
            <th>Pelamar</th>
            <th>Resume</th>
            <th>AI</th>
            <th>Status</th>
            <th>Tanggal</th>
            <th>Aksi</th>
          </tr>
        </thead>
        <tbody>
        @foreach($apps as $app)
          @php
            $score = $app->ai_score;
            if (!is_null($score)) {
                if ($score >= 75) { $badge='bg-success'; $label='High'; }
                elseif ($score >= 45) { $badge='bg-warning text-dark'; $label='Medium'; }
                else { $badge='bg-danger'; $label='Low'; }
            } else {
                $badge='bg-secondary'; $label='N/A';
            }

            $summary = $app->ai_notes
                ? preg_replace("/\r\n|\r|\n/", ' ', $app->ai_notes)
                : 'Belum dinilai';
          @endphp

          <tr>
            <td>{{ $app->id }}</td>

            <td>
              <div class="fw-bold">{{ $app->user->name }}</div>
              <div class="small">{{ $app->user->email }}</div>
            </td>

            <td>
              @if($app->resume_path)
                <a href="{{ route('employer.applications.resume', $app->id) }}"
                   class="btn btn-sm btn-outline-light">
                  Download
                </a>
              @else
                -
              @endif
            </td>

            <td>
              <span class="badge {{ $badge }}">
                {{ !is_null($score) ? number_format($score,1) : $label }}
              </span>
            </td>

            <td>{{ ucfirst($app->status) }}</td>

            <td>{{ $app->created_at->format('d M Y H:i') }}</td>

            <td>
              <form action="{{ route('employer.applications.status', $app->id) }}"
                    method="POST">
                @csrf
                <select name="status"
                        onchange="this.form.submit()"
                        class="form-select form-select-sm bg-dark text-light border-secondary">
                  <option value="applied" {{ $app->status=='applied'?'selected':'' }}>Applied</option>
                  <option value="viewed" {{ $app->status=='viewed'?'selected':'' }}>Viewed</option>
                  <option value="shortlisted" {{ $app->status=='shortlisted'?'selected':'' }}>Shortlisted</option>
                  <option value="interview" {{ $app->status=='interview'?'selected':'' }}>Interview</option>
                  <option value="rejected" {{ $app->status=='rejected'?'selected':'' }}>Rejected</option>
                  <option value="hired" {{ $app->status=='hired'?'selected':'' }}>Hired</option>
                </select>
              </form>
            </td>
          </tr>

          <tr>
            <td colspan="7" class="bg-secondary bg-opacity-25">
              <span class="badge {{ $badge }}">
                {{ !is_null($score) ? number_format($score,1) : $label }}
              </span>
              <span class="ms-2">{{ $summary }}</span>
            </td>
          </tr>
        @endforeach
        </tbody>
      </table>
    </div>

    {{ $apps->links() }}

  @else
    <p class="text-light">Tidak ada pelamar.</p>
  @endif

</div>
@endsection

