@extends('admin.layout')

@section('title','Companies')

@section('content')
<h1 class="mb-4">Companies</h1>

<form class="row g-2 mb-3" method="GET">
  <div class="col-auto">
    <input name="q" class="form-control" placeholder="Search by name" value="{{ $q ?? '' }}">
  </div>
  <div class="col-auto">
    <button class="btn btn-primary">Search</button>
  </div>
</form>

<table class="table table-dark table-striped">
  <thead>
    <tr>
      <th>Name</th>
      <th>Owner</th>
      <th>Verified</th>
      <th>Suspended</th>
      <th>Actions</th>
    </tr>
  </thead>
  <tbody>
  @foreach($companies as $company)
    <tr>
      <td><a href="{{ route('admin.companies.show', $company) }}">{{ $company->name }}</a></td>
      <td>{{ optional($company->owner)->name }}</td>
      <td>{!! $company->is_verified ? '<span class="badge bg-success">Yes</span>' : '<span class="badge bg-secondary">No</span>' !!}</td>
      <td>{!! $company->is_suspended ? '<span class="badge bg-danger">Yes</span>' : '<span class="badge bg-secondary">No</span>' !!}</td>
      <td>
        <a class="btn btn-sm btn-outline-light" href="{{ route('admin.companies.show', $company) }}">View</a>
      </td>
    </tr>
  @endforeach
  </tbody>
</table>

{{ $companies->withQueryString()->links() }}
@endsection

