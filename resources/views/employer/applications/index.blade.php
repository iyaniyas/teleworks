@extends('layouts.app')

@section('content')
<div class="container py-3">
  <h1 class="h4 mb-3 text-light">Daftar Pelamar</h1>

  @if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
  @endif

  @if($apps->count())
    <!-- DESKTOP TABLE -->
    <div class="table-responsive d-none d-md-block">
      <table class="table table-dark table-hover align-middle">
        <thead class="table-dark">
          <tr>
            <th>#</th>
            <th>Job</th>
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

            <!-- ROW UTAMA -->
            <tr>
              <td>{{ $app->id }}</td>

              <td>
                <a href="{{ route('jobs.show', $app->job->id) }}" class="text-light fw-semibold text-decoration-none">
                  {{ $app->job->title }}
                </a>
                <div class="small text-light">{{ $app->job->company ?? '' }}</div>
              </td>

              <td>
                <div class="fw-bold text-light">{{ $app->user->name }}</div>
                <div class="small text-light">{{ $app->user->email }}</div>
              </td>

              <td>
                @if($app->resume_path)
                  <a href="{{ route('employer.applications.resume', $app->id) }}" class="btn btn-sm btn-outline-light">Download</a>
                @else
                  <span class="text-light">-</span>
                @endif
              </td>

              <!-- Kolom AI kecil di row utama agar terlihat langsung -->
              <td>
                <span class="badge {{ $badge }} px-2 py-1 fw-bold">
                  @if(!is_null($score)) {{ number_format($score,1) }} @else {{ $label }} @endif
                </span>
              </td>

              <td class="text-light">{{ ucfirst($app->status) }}</td>

              <td class="text-light">{{ $app->created_at->format('d M Y H:i') }}</td>

              <td>
                <form action="{{ route('employer.applications.status', $app->id) }}"
                      method="POST" class="mb-0">
                  @csrf
                  <select name="status" onchange="this.form.submit()"
                          class="form-select form-select-sm bg-dark text-light border-secondary">
                    <option class="text-light bg-dark" value="applied" {{ $app->status=='applied'?'selected':'' }}>Applied</option>
                    <option class="text-light bg-dark" value="viewed" {{ $app->status=='viewed'?'selected':'' }}>Viewed</option>
                    <option class="text-light bg-dark" value="shortlisted" {{ $app->status=='shortlisted'?'selected':'' }}>Shortlisted</option>
                    <option class="text-light bg-dark" value="interview" {{ $app->status=='interview'?'selected':'' }}>Interview</option>
                    <option class="text-light bg-dark" value="rejected" {{ $app->status=='rejected'?'selected':'' }}>Rejected</option>
                    <option class="text-light bg-dark" value="hired" {{ $app->status=='hired'?'selected':'' }}>Hired</option>
                  </select>
                </form>
              </td>
            </tr>

            <!-- ROW AI SCORE (FULL WIDTH) -->
            <tr>
              <td colspan="8" class="bg-secondary bg-opacity-25 text-light">
                <div class="py-2 px-1">
                  <span class="badge {{ $badge }} px-2 py-1 fw-bold">
                    @if(!is_null($score)) {{ number_format($score,1) }} @else {{ $label }} @endif
                  </span>

                  <span class="ms-2">{{ $summary }}</span>
                </div>
              </td>
            </tr>

          @endforeach
        </tbody>
      </table>
    </div>

    <!-- MOBILE CARDS -->
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

        <div class="card bg-dark text-light mb-3 border-secondary">
          <div class="card-body">

            <!-- Top info -->
            <div class="d-flex justify-content-between">
              <div>
                <a href="{{ route('jobs.show', $app->job->id) }}" class="text-light fw-semibold text-decoration-none">
                  {{ $app->job->title }}
                </a>
                <div class="small text-light">{{ $app->job->company ?? '' }}</div>
              </div>

              <div class="text-end">
                <span class="badge {{ $badge }} px-2 py-1 fw-bold">
                  @if(!is_null($score)) {{ number_format($score,1) }} @else {{ $label }} @endif
                </span>
                <div class="small text-light">{{ $app->created_at->format('d M Y') }}</div>
              </div>
            </div>

            <!-- Applicant info -->
            <div class="mt-2">
              <strong class="text-light">{{ $app->user->name }}</strong>
              <div class="small text-light">{{ $app->user->email }}</div>
            </div>

            <!-- AI row -->
            <div class="mt-2 p-2 bg-secondary bg-opacity-25 rounded">
              <div class="fw-bold mb-1 text-light">AI Score</div>
              <span class="badge {{ $badge }} px-2 py-1 fw-bold">
                @if(!is_null($score)) {{ number_format($score,1) }} @else {{ $label }} @endif
              </span>
              <div class="mt-2 small text-light">
                {{ $summary }}
              </div>
            </div>

            <!-- Actions -->
            <div class="d-flex justify-content-between mt-3">
              <div>
                @if($app->resume_path)
                  <a href="{{ route('employer.applications.resume', $app->id) }}"
                     class="btn btn-sm btn-outline-light">Download CV</a>
                @endif
                <span class="badge bg-secondary ms-2 text-light">{{ ucfirst($app->status) }}</span>
              </div>
            </div>

            <form action="{{ route('employer.applications.status', $app->id) }}"
                  method="POST" class="mt-3">
              @csrf
              <select name="status"
                      onchange="this.form.submit()"
                      class="form-select form-select-sm bg-dark text-light border-secondary">
                <option class="text-light bg-dark" value="applied" {{ $app->status=='applied'?'selected':'' }}>Applied</option>
                <option class="text-light bg-dark" value="viewed" {{ $app->status=='viewed'?'selected':'' }}>Viewed</option>
                <option class="text-light bg-dark" value="shortlisted" {{ $app->status=='shortlisted'?'selected':'' }}>Shortlisted</option>
                <option class="text-light bg-dark" value="interview" {{ $app->status=='interview'?'selected':'' }}>Interview</option>
                <option class="text-light bg-dark" value="rejected" {{ $app->status=='rejected'?'selected':'' }}>Rejected</option>
                <option class="text-light bg-dark" value="hired" {{ $app->status=='hired'?'selected':'' }}>Hired</option>
              </select>
            </form>

          </div>
        </div>
      @endforeach
    </div>

    {{ $apps->links() }}

  @else
    <p class="text-light">Tidak ada pelamar.</p>
  @endif
</div>
@endsection

