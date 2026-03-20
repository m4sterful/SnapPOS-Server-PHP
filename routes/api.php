<?php

use App\Http\Controllers\Api\ApplicationStatusController;
use App\Http\Controllers\Api\ModuleController;
use Illuminate\Support\Facades\Route;

Route::middleware('setup.complete')->group(function (): void {
    Route::get('/', ApplicationStatusController::class)->name('home');

    Route::any('system', ModuleController::class)
        ->defaults('module', 'system')
        ->name('modules.system');

    foreach (['admin', 'marketing', 'purchasing', 'delivery', 'reporting', 'inventory', 'warehouse', 'operations'] as $module) {
        Route::get($module, ModuleController::class)
            ->defaults('module', $module)
            ->name("modules.{$module}");
    }
});
