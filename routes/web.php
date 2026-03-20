<?php

use App\Http\Controllers\HomeController;
use App\Http\Controllers\SetupController;
use Illuminate\Support\Facades\Route;

Route::middleware('setup.complete')->group(function (): void {
    Route::get('/', [HomeController::class, 'index'])->name('home');
});

Route::middleware('setup.incomplete')->group(function (): void {
    Route::get('/setup', [SetupController::class, 'show'])->name('setup.show');
    Route::post('/setup', [SetupController::class, 'store'])->name('setup.store');
});
