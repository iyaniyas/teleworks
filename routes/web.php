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

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application.
|
*/

// application
// Apply (user must be authenticated)
// Semua halaman *Pencari Kerja* hanya untuk role job_seeker
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

// Employer: view applications & change status (company or admin)
Route::middleware(['auth','role:company|admin'])->group(function() {
    Route::get('/employer/applications', [JobApplicationController::class, 'indexForEmployer'])->name('employer.applications');
    Route::post('/employer/applications/{id}/status', [JobApplicationController::class, 'changeStatus'])->name('employer.applications.status');
    Route::get('/employer/applications/{id}/resume', [JobApplicationController::class, 'downloadResume'])->name('employer.applications.resume');
});

// company creation (authenticated users)
Route::middleware('auth')->group(function () {
    Route::get('/company/create', [CompanyController::class, 'create'])->name('companies.create');
    Route::post('/company', [CompanyController::class, 'store'])->name('companies.store');
});

// public company profile
Route::get('/company/{slug}', [CompanyController::class, 'show'])->name('companies.show');

// edit jobs (authenticated)
Route::middleware('auth')->group(function () {
    Route::get('/loker/{id}/edit', [JobController::class, 'edit'])->name('jobs.edit');
    Route::patch('/loker/{id}', [JobController::class, 'update'])->name('jobs.update');
});

// route hanya untuk company
Route::middleware(['auth','role:company'])->group(function(){
    Route::get('/employer/dashboard', [\App\Http\Controllers\Employer\DashboardController::class, 'index'])->name('employer.dashboard');
});

// route hanya untuk admin
Route::middleware(['auth','role:admin'])->prefix('admin')->name('admin.')->group(function(){
    Route::get('/', [\App\Http\Controllers\Admin\AdminController::class,'dashboard'])->name('dashboard');
});

// Home (listing)
Route::get('/', [ListingController::class, 'home'])->name('home');

// AJAX endpoint for external jobs (Careerjet fallback) - returns JSON
Route::get('/ajax/external-jobs', [SearchController::class, 'externalJobsAjax'])
    ->name('search.external.ajax');

// PUBLIC: daftar pencarian terbaru
Route::get('/pencarian-terbaru', [\App\Http\Controllers\PublicSearchLogController::class, 'index'])
    ->name('public.searchlogs');

// SEO-friendly route for location-only: /cari/lokasi/{lokasi}
Route::get('/cari/lokasi/{lokasi}', [SearchController::class, 'slugLocation'])
    ->name('search.slug.location');

// SEO-friendly slug route (preferred)
Route::get('/cari/{kata}/{lokasi?}', [SearchController::class, 'slug'])
    ->name('search.slug');

// Search page
Route::get('/cari', [SearchController::class, 'index'])->name('search.index')->middleware('lowercase.query');

// Job detail
Route::get('/loker/{id}', [JobController::class, 'show'])->name('jobs.show');

// dashboard & auth (legacy/dashboard placeholder)
Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

// user profile (auth)
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

// logout
Route::post('/logout', function () {
    Auth::logout();
    return redirect('/');
})->name('logout')->middleware('auth');

// static pages
Route::view('/about', 'about')->name('about');
Route::view('/privacy', 'privacy')->name('privacy');

// auth routes (login/register/etc)
require __DIR__.'/auth.php';

