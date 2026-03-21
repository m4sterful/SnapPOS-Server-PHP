<?php

use App\Http\Controllers\Api\ApplicationStatusController;
use Illuminate\Support\Facades\Route;

Route::middleware('setup.complete')->group(function (): void {
    Route::get('/', ApplicationStatusController::class)->name('home');

    Route::name('modules.system.')
        ->prefix('system')
        ->group(base_path('routes/api/system.php'));

    foreach (['admin', 'marketing', 'purchasing', 'delivery', 'reporting', 'inventory', 'warehouse', 'operations'] as $domain) {
        Route::name("modules.{$domain}.")
            ->prefix($domain)
            ->group(base_path("routes/api/{$domain}.php"));
    }
});
