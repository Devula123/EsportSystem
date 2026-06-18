<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

Route::get('/dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::post('/tournaments', [\App\Http\Controllers\TournamentController::class, 'store'])->name('tournaments.store');
    Route::post('/tournaments/{tournament}/join', [\App\Http\Controllers\TournamentController::class, 'join'])->name('tournaments.join');
    Route::post('/tournaments/{tournament}/start', [\App\Http\Controllers\TournamentController::class, 'start'])->name('tournaments.start');

    Route::middleware(\App\Http\Middleware\EnsureUserIsAdmin::class)->group(function () {
        Route::get('/admin/tournaments', [\App\Http\Controllers\AdminTournamentController::class, 'index'])->name('admin.tournaments.index');
        Route::post('/admin/tournaments/{tournament}/approve', [\App\Http\Controllers\AdminTournamentController::class, 'approve'])->name('admin.tournaments.approve');
        Route::post('/admin/tournaments/{tournament}/reject', [\App\Http\Controllers\AdminTournamentController::class, 'reject'])->name('admin.tournaments.reject');
    });
});

require __DIR__.'/auth.php';
