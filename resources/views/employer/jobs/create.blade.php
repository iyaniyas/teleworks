@extends('layouts.app')

@section('title', 'Buat Lowongan')

@section('content')
<div class="container py-5" style="background:#14161c; min-height:70vh;">
  <div class="row justify-content-center">
    <div class="col-lg-10">
      <div class="card" style="background:#1c1f2a;color:#d1d6e3;">
        <div class="card-header"><strong>Buat Lowongan Baru</strong></div>
        <div class="card-body">

          <!-- Only placeholder fields use a light style -->
          <style>
            .lightfield {
              background: #ffffff !important;
              border: 1px solid #d2d2d2 !important;
              color: #222 !important;
            }
            .lightfield::placeholder {
              color: #555 !important;
              opacity: 1;
            }
          </style>

          @if(session('error')) <div class="alert alert-danger">{{ session('error') }}</div> @endif
          @if(session('success')) <div class="alert alert-success">{{ session('success') }}</div> @endif

          @if($errors->any())
            <div class="alert alert-danger">
              <ul class="mb-0">
                @foreach($errors->all() as $err)
                  <li>{{ $err }}</li>
                @endforeach
              </ul>
            </div>
          @endif

          <form id="createJobForm" action="{{ route('employer.jobs.store') }}" method="POST" novalidate>
            @csrf

            <div class="mb-3">
              <label class="form-label text-white">Judul <span class="text-danger">*</span></label>
              <input type="text" name="title" class="form-control"
                     value="{{ old('title') }}" required
                     style="background:#181b25;border:1px solid #33374a;color:#e4e7f5;">
              @error('title') <div class="small text-danger">{{ $message }}</div> @enderror
            </div>

            <div class="mb-3">
              <label class="form-label text-white">Deskripsi <span class="text-danger">*</span></label>
              <textarea name="description" rows="6"
                        class="form-control lightfield"
                        required placeholder="Deskripsikan pekerjaan...">{{ old('description') }}</textarea>
              @error('description') <div class="small text-danger">{{ $message }}</div> @enderror
            </div>

            <div class="row g-3">
              <div class="col-md-6 mb-3">
                <label class="form-label text-white">Lokasi Kerja <span class="text-danger">*</span></label>
                <input type="text" name="location"
                       class="form-control lightfield"
                       value="{{ old('location') }}" required
                       placeholder="Contoh: North Jakarta, Jakarta, Indonesia">
                <div class="small text-muted">Gunakan ini sebagai lokasi kerja (wajib).</div>
                @error('location') <div class="small text-danger">{{ $message }}</div> @enderror
              </div>

              <div class="col-md-6 mb-3">
                <label class="form-label text-white">Tipe Pekerjaan <span class="text-danger">*</span></label>
                <input type="text" name="employment_type"
                       class="form-control lightfield"
                       value="{{ old('employment_type') }}" required
                       placeholder="Full time / Part time / Contract">
                @error('employment_type') <div class="small text-danger">{{ $message }}</div> @enderror
              </div>
            </div>

            <div class="mb-3">
              <label class="form-label text-white">Kualifikasi Lokasi Pelamar <span class="text-danger">*</span></label>
              <!-- Made smaller: rows=2 and reduced height -->
              <textarea name="applicant_location_requirements"
                        rows="2"
                        class="form-control lightfield"
                        placeholder="Contoh: ID, US, SG atau Indonesia, Singapore"
                        style="height:70px;">{{ old('applicant_location_requirements') }}</textarea>
              <div class="small text-muted mt-1">Masukkan ISO country codes (ID, US) atau nama negara, pisahkan pakai koma atau baris baru.</div>
              @error('applicant_location_requirements') <div class="small text-danger">{{ $message }}</div> @enderror
            </div>

            <div class="row g-3">
              <div class="col-md-4 mb-3">
                <label class="form-label text-white">Remote <span class="text-danger">*</span></label>
                <select id="is_remote" class="form-select" name="is_remote" required style="background:#181b25;border:1px solid #33374a;color:#e4e7f5;">
                  <option value="">-- Pilih --</option>
                  <option value="1" {{ old('is_remote') == '1' ? 'selected' : '' }}>Ya (Remote)</option>
                  <option value="0" {{ old('is_remote') === '0' ? 'selected' : '' }}>Tidak (On-site / Hybrid)</option>
                </select>
                @error('is_remote') <div class="small text-danger">{{ $message }}</div> @enderror
              </div>

              <div class="col-md-4 mb-3">
                <label class="form-label text-white">Gaji Minimum <span class="text-danger">*</span></label>
                <input id="base_salary_min" type="number" step="0.01" name="base_salary_min" class="form-control" value="{{ old('base_salary_min') }}" required style="background:#181b25;border:1px solid #33374a;color:#e4e7f5;">
                @error('base_salary_min') <div class="small text-danger">{{ $message }}</div> @enderror
              </div>

              <div class="col-md-4 mb-3">
                <label class="form-label text-white">Gaji Maximum <span class="text-danger">*</span></label>
                <input id="base_salary_max" type="number" step="0.01" name="base_salary_max" class="form-control" value="{{ old('base_salary_max') }}" required style="background:#181b25;border:1px solid #33374a;color:#e4e7f5;">
                @error('base_salary_max') <div class="small text-danger">{{ $message }}</div> @enderror
              </div>
            </div>

            <div class="mb-3">
              <label class="form-label text-white">Tanggal Dipublikasikan (date_posted)</label>
              <input type="date" name="date_posted" class="form-control" value="{{ old('date_posted', \Carbon\Carbon::now()->format('Y-m-d')) }}" style="background:#181b25;border:1px solid #33374a;color:#e4e7f5;">
              <div class="small text-muted">Kosongkan untuk gunakan tanggal hari ini.</div>
            </div>

            <div class="row g-3 mb-3">
              <div class="col-md-6">
                <label class="form-label text-white">Cara Melamar <span class="text-danger">*</span></label>
                <select id="apply_via" name="apply_via" class="form-select" required style="background:#181b25;border:1px solid #33374a;color:#e4e7f5;">
                  <option value="">-- Pilih --</option>
                  <option value="teleworks" {{ old('apply_via')=='teleworks' ? 'selected':'' }}>Seleksi melalui sistem teleworks.</option>
                  <option value="external" {{ old('apply_via')=='external' ? 'selected':'' }}>Lamaran dikirim ke Situs/WA/Email Anda.</option>
                </select>
              </div>

              <div class="col-md-6" id="applyContactWrapper" style="{{ old('apply_via')=='external' ? '' : 'display:none;' }}">
                <label class="form-label text-white">Link / WA / Email </label>
                <input type="text" name="apply_contact" id="apply_contact" class="form-control lightfield" value="{{ old('apply_contact') }}" placeholder="contoh:https://wa.me/628221000, mailto:kontak@gmail.com">
                <div class="small text-muted">Jika memilih "Situs/WA/Email", isi link atau kontak di sini.</div>
              </div>
            </div>

            <div class="mb-3">
              <label class="form-label text-white">Tanggal Expire <span class="text-danger">*</span></label>
              <input id="expires_at" type="date" name="expires_at" class="form-control" value="{{ old('expires_at', \Carbon\Carbon::now()->addDays(45)->format('Y-m-d')) }}" required style="background:#181b25;border:1px solid #33374a;color:#e4e7f5;">
              @error('expires_at') <div class="small text-danger">{{ $message }}</div> @enderror
            </div>

            <div id="clientError" class="alert alert-danger d-none"></div>

            <div class="d-grid">
              <button id="submitBtn" type="submit" class="btn btn-primary">Simpan</button>
            </div>

          </form>
        </div>
      </div>
    </div>
  </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function(){
  const form = document.getElementById('createJobForm');
  const minInput = document.getElementById('base_salary_min');
  const maxInput = document.getElementById('base_salary_max');
  const clientError = document.getElementById('clientError');
  const applyVia = document.getElementById('apply_via');
  const applyContactWrapper = document.getElementById('applyContactWrapper');
  const applyContactInput = document.getElementById('apply_contact');

  if (applyVia) {
    applyVia.addEventListener('change', function(){
      if (this.value === 'external') {
        applyContactWrapper.style.display = '';
        if (applyContactInput) applyContactInput.setAttribute('required','required');
      } else {
        applyContactWrapper.style.display = 'none';
        if (applyContactInput) applyContactInput.removeAttribute('required');
      }
    });
  }

  if (form) {
    form.addEventListener('submit', function(e){
      clientError.classList.add('d-none');
      clientError.innerHTML = '';

      const isRemote = document.getElementById('is_remote').value;
      const minVal = parseFloat(minInput.value);
      const maxVal = parseFloat(maxInput.value);
      const expiresAt = document.getElementById('expires_at').value;
      const applyViaVal = applyVia ? applyVia.value : '';
      const applyContactVal = applyContactInput ? applyContactInput.value.trim() : '';
      let errs = [];

      if (!document.querySelector('[name="location"]').value.trim()) errs.push('Lokasi kerja wajib diisi.');
      if (isRemote === '') errs.push('Silakan pilih apakah pekerjaan bersifat remote atau tidak.');
      if (isNaN(minVal)) errs.push('Gaji minimum wajib diisi dengan angka.');
      if (isNaN(maxVal)) errs.push('Gaji maksimum wajib diisi dengan angka.');
      if (!expiresAt) errs.push('Tanggal expire wajib diisi.');
      if (!isNaN(minVal) && !isNaN(maxVal) && minVal > maxVal) errs.push('Gaji minimum tidak boleh lebih besar dari gaji maksimum.');
      if (!applyViaVal) errs.push('Silakan pilih cara melamar.');
      if (applyViaVal === 'external' && (!applyContactVal || applyContactVal.length < 3)) errs.push('Isi link/kontak melamar untuk opsi eksternal.');

      if (errs.length) {
        e.preventDefault();
        clientError.innerHTML = '<ul class="mb-0"><li>' + errs.join('</li><li>') + '</li></ul>';
        clientError.classList.remove('d-none');
        window.scrollTo({ top: clientError.getBoundingClientRect().top + window.scrollY - 80, behavior: 'smooth' });
        return false;
      }

      return true;
    });
  }
});
</script>
@endpush

@endsection

