<?php

use App\Http\Controllers\Sondeo\SondeoPageController;
use App\Http\Controllers\Sondeo\SondeoResultsController;
use App\Http\Controllers\Sondeo\SondeoVoteController;
use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Features;

/*
| Sondeo ciudadano: portada pública. Auth opcional en /welcome y dashboard.
*/
Route::get('/', SondeoPageController::class)->name('sondeo.home');

Route::get('/api/sondeo/results', SondeoResultsController::class)->name('sondeo.results');

Route::post('/api/sondeo/vote', SondeoVoteController::class)
    ->middleware('throttle:'.config('sondeo.vote_throttle_per_minute', 5).',1')
    ->name('sondeo.vote');

Route::inertia('/welcome', 'Welcome', [
    'canRegister' => Features::enabled(Features::registration()),
])->name('home.legacy');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'Dashboard')->name('dashboard');
});

require __DIR__.'/settings.php';
