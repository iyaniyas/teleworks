<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\ListingController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\JobController;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\JobApplicationController;
use App\Http\Controllers\Seeker\DashboardController as SeekerDashboardController;
use App\Http\Controllers\Seeker\ProfileController as SeekerProfileController;
use App\Http\Controllers\Seeker\ApplicationController as SeekerApplicationController;
use App\Http\Controllers\Seeker\SavedController as SeekerSavedController;
use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\Admin\CompanyController as AdminCompanyController;
use App\Http\Controllers\Admin\JobController as AdminJobController;
use App\Http\Controllers\Admin\ReportController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application.
|
*/

/*
|--------------------------------------------------------------------------
| Employer routes (company-only)
|--------------------------------------------------------------------------
|
| Group baru yang menampung dashboard employer + fitur perusahaan (jobs, applicants,
| company profile, team, billing). Middleware: auth + role:company
|
*/
Route::group([
    'prefix' => 'employer',
    'as' => 'employer.',
    'middleware' => ['auth', 'role:company']
], function () {
    // Dashboard (company)
    Route::get('/dashboard', [App\Http\Controllers\Employer\DashboardController::class, 'index'])->name('dashboard');

    // Jobs (resource)
    Route::resource('jobs', App\Http\Controllers\Employer\JobController::class);

    // Applicants for a job (index) + actions on applicants
    Route::get('jobs/{job}/applicants', [App\Http\Controllers\Employer\ApplicantController::class, 'index'])
        ->name('jobs.applicants.index');
    Route::post('applicants/{applicant}/status', [App\Http\Controllers\Employer\ApplicantController::class, 'updateStatus'])
        ->name('applicants.updateStatus');
    Route::post('applicants/{applicant}/note', [App\Http\Controllers\Employer\ApplicantController::class, 'addNote'])
        ->name('applicants.addNote');

    // Company profile (edit/update)
    Route::get('company', [App\Http\Controllers\Employer\CompanyController::class, 'edit'])->name('company.edit');
    Route::post('company', [App\Http\Controllers\Employer\CompanyController::class, 'update'])->name('company.update');

    // Team management (resource, without show)
    // Route::resource('team', App\Http\Controllers\Employer\TeamController::class)->except(['show']);

    // Billing (stubs)
    // Route::get('billing', [App\Http\Controllers\Employer\BillingController::class, 'index'])->name('billing.index');
    // Route::post('billing/checkout', [App\Http\Controllers\Employer\BillingController::class, 'checkout'])->name('billing.checkout');
});

/*
|--------------------------------------------------------------------------
| Application (Seeker-only) routes
|--------------------------------------------------------------------------
| Semua halaman *Pencari Kerja* hanya untuk role job_seeker
*/
Route::middleware(['auth','role:job_seeker'])->group(function(){
    // Seeker dashboard
    Route::get('/seeker/dashboard', [SeekerDashboardController::class, 'index'])->name('seeker.dashboard');

    // profile
    Route::get('/seeker/profile', [SeekerProfileController::class, 'edit'])->name('seeker.profile.edit');
    Route::patch('/seeker/profile', [SeekerProfileController::class, 'update'])->name('seeker.profile.update');

    // applications
    Route::get('/seeker/applications', [SeekerApplicationController::class, 'index'])->name('seeker.applications.index');
    Route::post('/seeker/applications/{id}/withdraw', [SeekerApplicationController::class, 'withdraw'])->name('seeker.applications.withdraw');

    // saved/bookmark
    Route::get('/seeker/saved', [SeekerSavedController::class, 'index'])->name('seeker.saved.index');
    Route::post('/jobs/{id}/bookmark', [SeekerSavedController::class, 'toggle'])->name('jobs.bookmark');

    // apply to job (seeker)
    Route::post('/loker/{id}/apply', [JobApplicationController::class, 'apply'])->name('jobs.apply');
});

/*
|--------------------------------------------------------------------------
| Employer: view applications & change status (company or admin)
|--------------------------------------------------------------------------
| Route ini spesifik untuk pengelolaan aplikasi oleh perusahaan atau admin.
| Tetap dibiarkan terpisah karena middleware mengizinkan juga 'admin'.
*/
Route::middleware(['auth','role:company|admin'])->group(function() {
    Route::get('/employer/applications', [JobApplicationController::class, 'indexForEmployer'])->name('employer.applications');
    Route::post('/employer/applications/{id}/status', [JobApplicationController::class, 'changeStatus'])->name('employer.applications.status');
    Route::get('/employer/applications/{id}/resume', [JobApplicationController::class, 'downloadResume'])->name('employer.applications.resume');
});

/*
|--------------------------------------------------------------------------
| Company creation (authenticated users)
|--------------------------------------------------------------------------
*/
Route::middleware('auth')->group(function () {
    Route::get('/company/create', [CompanyController::class, 'create'])->name('companies.create');
    Route::post('/company', [CompanyController::class, 'store'])->name('companies.store');
});

/*
|--------------------------------------------------------------------------
| Company edit (only owner or admin)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth','role:company|admin'])->group(function () {
    Route::get('/company/{company}/edit', [CompanyController::class, 'edit'])->name('companies.edit');
    Route::patch('/company/{company}', [CompanyController::class, 'update'])->name('companies.update');
});

/*
|--------------------------------------------------------------------------
| Public company profile
|--------------------------------------------------------------------------
*/
Route::get('/company/{slug}', [CompanyController::class, 'show'])->name('companies.show');

/*
|--------------------------------------------------------------------------
| Edit jobs (authenticated)
|--------------------------------------------------------------------------
*/
Route::middleware('auth')->group(function () {
    Route::get('/loker/{id}/edit', [JobController::class, 'edit'])->name('jobs.edit');
    Route::patch('/loker/{id}', [JobController::class, 'update'])->name('jobs.update');
});

/*
|--------------------------------------------------------------------------
| Admin routes
|--------------------------------------------------------------------------
*/
Route::middleware(['auth','role:admin'])->prefix('admin')->name('admin.')->group(function(){
    Route::get('/', [AdminController::class, 'dashboard'])->name('dashboard');

    // Companies
    Route::get('companies', [AdminCompanyController::class,'index'])->name('companies.index');
    Route::get('companies/{company}', [AdminCompanyController::class,'show'])->name('companies.show');
    Route::post('companies/{company}/verify', [AdminCompanyController::class,'verify'])->name('companies.verify');
    Route::post('companies/{company}/unverify', [AdminCompanyController::class,'unverify'])->name('companies.unverify');
    Route::post('companies/{company}/suspend', [AdminCompanyController::class,'suspend'])->name('companies.suspend');
    Route::post('companies/{company}/unsuspend', [AdminCompanyController::class,'unsuspend'])->name('companies.unsuspend');

    // Jobs
    Route::get('jobs', [AdminJobController::class,'index'])->name('jobs.index');
    Route::get('jobs/{job}/edit', [AdminJobController::class,'edit'])->name('jobs.edit');
    Route::put('jobs/{job}', [AdminJobController::class,'update'])->name('jobs.update');
    Route::delete('jobs/{job}', [AdminJobController::class,'destroy'])->name('jobs.destroy');

    // Reports
    Route::get('reports', [ReportController::class,'index'])->name('reports.index');
    Route::get('reports/{report}', [ReportController::class,'show'])->name('reports.show');
    Route::post('reports/{report}/resolve', [ReportController::class,'resolve'])->name('reports.resolve');
});

/*
|--------------------------------------------------------------------------
| Home (listing)
|--------------------------------------------------------------------------
*/
Route::get('/', [ListingController::class, 'home'])->name('home');

/*
|--------------------------------------------------------------------------
| AJAX endpoint for external jobs (Careerjet fallback) - returns JSON
|--------------------------------------------------------------------------
*/
Route::get('/ajax/external-jobs', [SearchController::class, 'externalJobsAjax'])
    ->name('search.external.ajax');

/*
|--------------------------------------------------------------------------
| PUBLIC: daftar pencarian terbaru
|--------------------------------------------------------------------------
*/
Route::get('/pencarian-terbaru', [\App\Http\Controllers\PublicSearchLogController::class, 'index'])
    ->name('public.searchlogs');

/*
|--------------------------------------------------------------------------
| SEO-friendly search routes
|--------------------------------------------------------------------------
*/
Route::get('/cari/lokasi/{lokasi}', [SearchController::class, 'slugLocation'])
    ->name('search.slug.location');

Route::get('/cari/{kata}/{lokasi?}', [SearchController::class, 'slug'])
    ->name('search.slug');

Route::get('/cari', [SearchController::class, 'index'])->name('search.index')->middleware('lowercase.query');

/*
|--------------------------------------------------------------------------
| Job detail
|--------------------------------------------------------------------------
*/
Route::get('/loker/{id}', [JobController::class, 'show'])->name('jobs.show');

/*
|--------------------------------------------------------------------------
| Dashboard & auth (legacy/dashboard placeholder)
|--------------------------------------------------------------------------
*/
Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

/*
|--------------------------------------------------------------------------
| User profile (auth)
|--------------------------------------------------------------------------
*/
Route::middleware('auth')->group(function () {
    // Lihat profil
    Route::get('/profile', [ProfileController::class, 'show'])->name('profile.show');

    // Edit profil
    Route::get('/profile/edit', [ProfileController::class, 'edit'])->name('profile.edit');

    // Update profil
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');

    // Hapus akun
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

/*
|--------------------------------------------------------------------------
| Logout
|--------------------------------------------------------------------------
*/
Route::post('/logout', function () {
    Auth::logout();
    return redirect('/');
})->name('logout')->middleware('auth');

// hanya user login bisa kirim report
Route::middleware(['auth', 'throttle:10,1'])->post('/reports', [ReportController::class, 'store'])->name('reports.store');

/*
|--------------------------------------------------------------------------
| Static pages
|--------------------------------------------------------------------------
*/
Route::view('/about', 'about')->name('about');
Route::view('/privacy', 'privacy')->name('privacy');

/*
|--------------------------------------------------------------------------
| Auth routes (login/register/etc)
|--------------------------------------------------------------------------
*/
require __DIR__.'/auth.php';

