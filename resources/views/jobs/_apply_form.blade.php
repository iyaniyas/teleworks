@auth
  <div class="card mt-4">
    <div class="card-body">
      <h5>Lamaran</h5>

      @if(session('success')) <div class="alert alert-success">{{ session('success') }}</div> @endif
      @if(session('error')) <div class="alert alert-danger">{{ session('error') }}</div> @endif

      <form action="{{ route('jobs.apply', $job->id) }}" method="POST" enctype="multipart/form-data">
        @csrf

        <div class="mb-3">
          <label class="form-label">Upload CV (PDF, DOC, DOCX) — maks 5MB</label>
          <input type="file" name="resume" class="form-control" accept=".pdf,.doc,.docx">
          @error('resume') <div class="text-danger">{{ $message }}</div> @enderror
        </div>

        <div class="mb-3">
          <label class="form-label">Cover letter (opsional)</label>
          <textarea name="cover_letter" class="form-control" rows="4">{{ old('cover_letter') }}</textarea>
          @error('cover_letter') <div class="text-danger">{{ $message }}</div> @enderror
        </div>

        <button class="btn btn-primary">Kirim Lamaran</button>
      </form>
    </div>
  </div>
@else
  <p><a href="{{ route('login') }}">Login</a> untuk melamar pekerjaan ini.</p>
@endauth

