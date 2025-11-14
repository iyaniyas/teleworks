<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ListingController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\JobController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// Home (listing)
Route::get('/', [ListingController::class, 'home'])->name('home');

// SEO-friendly route for location-only: /cari/lokasi/{lokasi}
Route::get('/cari/lokasi/{lokasi}', [SearchController::class, 'slugLocation'])
    ->name('search.slug.location');

// SEO-friendly slug route (preferred)
Route::get('/cari/{kata}/{lokasi?}', [SearchController::class, 'slug'])
    ->name('search.slug');

// Legacy route (form submits here; middleware will redirect to slug when needed)

// Search page
Route::get('/cari', [SearchController::class, 'index'])->name('search.index')->middleware('lowercase.query');

// Job detail
Route::get('/loker/{id}', [JobController::class, 'show'])->name('jobs.show');

// dashboard & auth
Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';

