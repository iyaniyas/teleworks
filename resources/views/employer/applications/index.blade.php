@extends('layouts.app')

@section('content')
<div class="container">
  <h1>Daftar Pelamar</h1>

  @if(session('success')) <div class="alert alert-success">{{ session('success') }}</div> @endif

  @if($apps->count())
    <table class="table">
      <thead>
        <tr>
          <th>#</th>
          <th>Job</th>
          <th>Pelamar</th>
          <th>Resume</th>
          <th>Status</th>
          <th>Tanggal</th>
          <th>Aksi</th>
        </tr>
      </thead>
      <tbody>
        @foreach($apps as $app)
          <tr>
            <td>{{ $app->id }}</td>
            <td><a href="{{ route('jobs.show', $app->job->id) }}">{{ $app->job->title }}</a></td>
            <td>{{ $app->user->name }}<br><small>{{ $app->user->email }}</small></td>
            <td>
              @if($app->resume_path)
                <a href="{{ route('employer.applications.resume', $app->id) }}">Download</a>
              @else
                -
              @endif
            </td>
            <td>{{ ucfirst($app->status) }}</td>
            <td>{{ $app->created_at->format('d M Y H:i') }}</td>
            <td>
              <form action="{{ route('employer.applications.status', $app->id) }}" method="POST" style="display:inline">
                @csrf
                <select name="status" onchange="this.form.submit()" class="form-select">
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
        @endforeach
      </tbody>
    </table>

    {{ $apps->links() }}
  @else
    <p>Tidak ada pelamar.</p>
  @endif
</div>
@endsection

