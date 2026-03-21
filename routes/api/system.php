<?php

use App\Http\Controllers\Api\ModuleController;
use App\Http\Controllers\Api\SystemSchemaValidationController;
use Illuminate\Support\Facades\Route;

Route::get('/ping', ModuleController::class)
    ->defaults('module', 'system')
    ->name('ping');

Route::get('/schema-validation', SystemSchemaValidationController::class)
    ->name('schema-validation');
