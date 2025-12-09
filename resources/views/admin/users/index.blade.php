@extends('layouts.app')

@section('title', 'Admin - Users')

@section('content')
<div class="container py-5">
  <div class="row justify-content-center">
    <div class="col-lg-11">

      <div class="mb-4 d-flex justify-content-between align-items-center">
        <div>
          <h3 class="fw-semibold text-light">Manajemen User</h3>
          <div class="text-secondary">
            Lihat dan kelola akun pencari kerja, perusahaan, dan admin.
          </div>
        </div>
      </div>

      {{-- Filter & search --}}
      <div class="card bg-dark border-secondary mb-3 text-light">
        <div class="card-body">
          <form method="GET" action="{{ route('admin.users.index') }}" class="row g-2 align-items-end">

            <div class="col-md-3">
              <label class="form-label small text-secondary">Role</label>
              <select name="role" class="form-select form-select-sm bg-dark text-light border-secondary">
                <option value="">Semua</option>
                @foreach($availableRoles as $r)
                  <option value="{{ $r }}" @selected($role === $r)>
                    {{ ucfirst(str_replace('_',' ',$r)) }}
                  </option>
                @endforeach
              </select>
            </div>

            <div class="col-md-4">
              <label class="form-label small text-secondary">Cari (nama / email)</label>
              <input type="text" name="q"
                     class="form-control form-control-sm bg-dark text-light border-secondary"
                     value="{{ $q }}" placeholder="misal: budi / budi@mail.com">
            </div>

            <div class="col-md-3">
              <button type="submit" class="btn btn-sm btn-primary">
                Terapkan
              </button>
              <a href="{{ route('admin.users.index') }}"
                 class="btn btn-sm btn-outline-secondary ms-1">
                Reset
              </a>
            </div>
          </form>
        </div>
      </div>

      {{-- Flash message --}}
      @if(session('status'))
        <div class="alert alert-success py-2">{{ session('status') }}</div>
      @endif

      @if(session('error'))
        <div class="alert alert-danger py-2">{{ session('error') }}</div>
      @endif

      {{-- Table --}}
      <div class="card bg-dark border-secondary text-light">
        <div class="card-body p-0">
          <div class="table-responsive">

            <table class="table table-sm table-dark table-hover align-middle mb-0">

              <thead class="table-secondary text-dark">
                <tr>
                  <th class="small">ID</th>
                  <th class="small">Nama</th>
                  <th class="small">Email</th>
                  <th class="small">Role</th>
                  <th class="small">Perusahaan</th>
                  <th class="small">Dibuat</th>
                  <th class="small text-end">Aksi</th>
                </tr>
              </thead>

              <tbody>
              @forelse($users as $user)
                @php
                  $roleNames = $user->getRoleNames();
                  $mainRole  = $roleNames->first();
                  $companyName = null;

                  if ($user->company) {
                      $companyName = $user->company->name;
                  } elseif ($user->companies && $user->companies->count() > 0) {
                      $companyName = $user->companies->first()->name;
                  }
                @endphp

                <tr>
                  <td class="small">{{ $user->id }}</td>

                  <td class="small">
                    {{ $user->name }}
                    @if($user->profile && $user->profile->headline)
                      <div class="text-secondary small">
                        {{ $user->profile->headline }}
                      </div>
                    @endif
                  </td>

                  <td class="small">{{ $user->email }}</td>

                  <td class="small">
                    @if($mainRole)
                      <span class="badge bg-secondary">{{ $mainRole }}</span>
                    @else
                      <span class="badge bg-dark border border-secondary">-</span>
                    @endif
                  </td>

                  <td class="small">
                    {{ $companyName ?? '-' }}
                  </td>

                  <td class="small">
                    {{ optional($user->created_at)->format('d M Y') }}
                  </td>

                  <td class="small text-end">
                    <a href="{{ route('admin.users.edit', $user) }}"
                       class="btn btn-sm btn-outline-light">
                      Edit
                    </a>

                    <form action="{{ route('admin.users.destroy', $user) }}"
                          method="POST" class="d-inline"
                          onsubmit="return confirm('Yakin ingin menghapus user ini?');">
                      @csrf
                      @method('DELETE')
                      <button class="btn btn-sm btn-outline-danger" type="submit">
                        Hapus
                      </button>
                    </form>
                  </td>
                </tr>

              @empty
                <tr>
                  <td colspan="7" class="text-center small text-secondary py-3">
                    Tidak ada user ditemukan.
                  </td>
                </tr>
              @endforelse
              </tbody>
            </table>
          </div>

          <div class="p-3 d-flex justify-content-end">
            {{ $users->links() }}
          </div>

        </div>
      </div>

    </div>
  </div>
</div>
@endsection

