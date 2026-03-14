<?php

use App\Http\Controllers\Sondeo\SondeoPageController;
use App\Http\Controllers\Sondeo\SondeoResultsController;
use App\Http\Controllers\Sondeo\SondeoSitemapController;
use App\Http\Controllers\Sondeo\SondeoSuggestionController;
use App\Http\Controllers\Sondeo\SondeoVoteController;
use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Features;

/*
| Sondeo ciudadano: portada pública. Auth opcional en /welcome y dashboard.
*/
Route::get('/', SondeoPageController::class)->name('sondeo.home');
Route::get('/sitemap.xml', SondeoSitemapController::class)->name('sondeo.sitemap');

Route::get('/api/sondeo/results', SondeoResultsController::class)
    ->middleware('throttle:'.(int) config('sondeo.results_throttle_per_minute', 90).',1')
    ->name('sondeo.results');

Route::post('/api/sondeo/vote', SondeoVoteController::class)
    ->middleware([
        'throttle:'.config('sondeo.vote_throttle_per_minute', 5).',1',
        'sondeo.vote.security',
    ])
    ->name('sondeo.vote');

Route::post('/api/sondeo/suggestion', SondeoSuggestionController::class)
    ->middleware('throttle:'.(int) config('sondeo.suggestion_throttle_per_minute', 4).',1')
    ->name('sondeo.suggestion');

Route::inertia('/welcome', 'Welcome', [
    'canRegister' => Features::enabled(Features::registration()),
])->name('home.legacy');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'Dashboard')->name('dashboard');
});

require __DIR__.'/settings.php';
