@extends('layouts.app')

@section('title', 'Edit Lowongan')

@section('content')
<div class="container py-5" style="background:#14161c; min-height:70vh;">
  <div class="row justify-content-center">
    <div class="col-lg-10">
      <div class="card" style="background:#1c1f2a;color:#d1d6e3;">
        <div class="card-header"><strong>Edit Lowongan</strong></div>
        <div class="card-body">
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

          <form id="editJobForm" action="{{ route('employer.jobs.update', $job->id) }}" method="POST" novalidate>
            @csrf
            @method('PATCH')

            <div class="mb-3">
              <label class="form-label text-white">Judul <span class="text-danger">*</span></label>
              <input type="text" name="title" class="form-control" value="{{ old('title', $job->title) }}" required style="background:#181b25;border:1px solid #33374a;color:#e4e7f5;">
            </div>

            <div class="mb-3">
              <label class="form-label text-white">Deskripsi <span class="text-danger">*</span></label>
              <textarea name="description" rows="6" class="form-control" required style="background:#181b25;border:1px solid #33374a;color:#e4e7f5;">{{ old('description', $job->description) }}</textarea>
            </div>

            <div class="row g-3">
              <div class="col-md-6 mb-3">
                <label class="form-label text-white">Lokasi Kerja <span class="text-danger">*</span></label>
                <input type="text" name="location" class="form-control" value="{{ old('location', $job->location) }}" required placeholder="Contoh: North Jakarta, Jakarta, Indonesia" style="background:#181b25;border:1px solid #33374a;color:#e4e7f5;">
                <div class="small text-muted">Gunakan ini sebagai lokasi kerja (wajib).</div>
                @error('location') <div class="small text-danger">{{ $message }}</div> @enderror
              </div>

              <div class="col-md-6 mb-3">
                <label class="form-label text-white">Tipe Pekerjaan <span class="text-danger">*</span></label>
                <input type="text" name="employment_type" class="form-control" value="{{ old('employment_type', $job->employment_type) }}" placeholder="Full time / Part time / Contract" required style="background:#181b25;border:1px solid #33374a;color:#e4e7f5;">
              </div>
            </div>

            <div class="mb-3">
              <label class="form-label text-white">Kualifikasi Lokasi Pelamar <span class="text-danger">*</span></label>
              <textarea name="applicant_location_requirements" rows="3" class="form-control" placeholder="Contoh: ID, US, SG atau Indonesia, Singapore" required style="background:#181b25;border:1px solid #33374a;color:#e4e7f5;">{{ old('applicant_location_requirements', is_array($apprArr) ? implode(', ', $apprArr) : ($job->applicant_location_requirements ?? '')) }}</textarea>
              <div class="small text-muted mt-1">Masukkan ISO country codes (ID, US) atau nama negara, pisahkan pakai koma atau baris baru.</div>
            </div>

            <div class="row g-3">
              <div class="col-md-4 mb-3">
                <label class="form-label text-white">Remote <span class="text-danger">*</span></label>
                <select id="is_remote" class="form-select" name="is_remote" required style="background:#181b25;border:1px solid #33374a;color:#e4e7f5;">
                  <option value="">-- Pilih --</option>
                  <option value="1" {{ old('is_remote', $job->is_remote) == '1' ? 'selected' : '' }}>Ya (Remote)</option>
                  <option value="0" {{ (string)old('is_remote', $job->is_remote) === '0' ? 'selected' : '' }}>Tidak (On-site / Hybrid)</option>
                </select>
              </div>

              <div class="col-md-4 mb-3">
                <label class="form-label text-white">Gaji Minimum <span class="text-danger">*</span></label>
                <input id="base_salary_min" type="number" step="0.01" name="base_salary_min" class="form-control" value="{{ old('base_salary_min', $job->base_salary_min) }}" required style="background:#181b25;border:1px solid #33374a;color:#e4e7f5;">
              </div>

              <div class="col-md-4 mb-3">
                <label class="form-label text-white">Gaji Maximum <span class="text-danger">*</span></label>
                <input id="base_salary_max" type="number" step="0.01" name="base_salary_max" class="form-control" value="{{ old('base_salary_max', $job->base_salary_max) }}" required style="background:#181b25;border:1px solid #33374a;color:#e4e7f5;">
              </div>
            </div>

            <div class="mb-3">
              <label class="form-label text-white">Tanggal Dipublikasikan (date_posted)</label>
              <input type="date" name="date_posted" class="form-control" value="{{ old('date_posted', optional($job->date_posted)->format('Y-m-d') ?? \Carbon\Carbon::now()->format('Y-m-d')) }}" style="background:#181b25;border:1px solid #33374a;color:#e4e7f5;">
            </div>

            <div class="row g-3 mb-3">
              <div class="col-md-6">
                <label class="form-label text-white">Cara Melamar <span class="text-danger">*</span></label>
                @php
                  $curApplyVia = old('apply_via');
                  if (!$curApplyVia) {
                      $curApplyVia = ($job->direct_apply ? 'teleworks' : 'external');
                  }
                @endphp
                <select id="apply_via" name="apply_via" class="form-select" required style="background:#181b25;border:1px solid #33374a;color:#e4e7f5;">
                  <option value="">-- Pilih --</option>
                  <option value="teleworks" {{ $curApplyVia == 'teleworks' ? 'selected' : '' }}>Kirim via Teleworks (langsung)</option>
                  <option value="external" {{ $curApplyVia == 'external' ? 'selected' : '' }}>Situs/WA/Email (eksternal)</option>
                </select>
              </div>

              <div class="col-md-6" id="applyContactWrapper" style="{{ $curApplyVia == 'external' ? '' : 'display:none;' }}">
                <label class="form-label text-white">Link / Kontak Melamar (apply_contact)</label>
                @php
                  $curApplyContact = old('apply_contact', $job->apply_url);
                  if ($job->direct_apply) {
                      if (stripos($curApplyContact, url('/loker/'.$job->id)) !== false) {
                          $curApplyContact = old('apply_contact', '');
                      }
                  }
                @endphp
                <input type="text" name="apply_contact" id="apply_contact" class="form-control" value="{{ $curApplyContact }}" placeholder="https://, mailto:, https://wa.me/62..." style="background:#181b25;border:1px solid #33374a;color:#e4e7f5;">
                <div class="small text-muted">Jika memilih "Situs/WA/Email", isi link atau kontak di sini.</div>
              </div>
            </div>

            <div class="mb-3">
              <label class="form-label text-white">Tanggal Expire <span class="text-danger">*</span></label>
              <input id="expires_at" type="date" name="expires_at" class="form-control" value="{{ old('expires_at', optional($job->expires_at)->format('Y-m-d')) }}" required style="background:#181b25;border:1px solid #33374a;color:#e4e7f5;">
            </div>

            <div id="clientError" class="alert alert-danger d-none"></div>

            <div class="d-grid">
              <button id="submitBtn" type="submit" class="btn btn-primary">Simpan Perubahan</button>
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
  const form = document.getElementById('editJobForm');
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

