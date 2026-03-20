<?php

use App\Http\Controllers\SetupController;
use App\Support\Setup\InstallationStatus;
use Illuminate\Support\Facades\Route;

Route::get('/', function (InstallationStatus $installationStatus) {
    abort_if($installationStatus->installed(), 404);

    return redirect()->route('setup.show');
})->name('setup.entry');

Route::middleware('setup.incomplete')->group(function (): void {
    Route::get('/setup', [SetupController::class, 'show'])->name('setup.show');
    Route::post('/setup', [SetupController::class, 'store'])->name('setup.store');
});
