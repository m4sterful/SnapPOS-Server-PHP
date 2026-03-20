<?php

use App\Http\Controllers\Api\ApplicationStatusController;
use App\Http\Controllers\Api\ModuleController;
use Illuminate\Support\Facades\Route;

Route::middleware('setup.complete')->group(function (): void {
    Route::get('/', ApplicationStatusController::class)->name('home');

    foreach (['admin', 'marketing', 'purchasing', 'delivery', 'reporting', 'inventory', 'warehouse', 'operations', 'system'] as $module) {
        Route::get($module, ModuleController::class)
            ->defaults('module', $module)
            ->name("modules.{$module}");
    }
});
