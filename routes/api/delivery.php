<?php

use App\Http\Controllers\Api\ModuleController;
use Illuminate\Support\Facades\Route;

Route::get('/', ModuleController::class)
    ->defaults('module', 'delivery')
    ->name('index');
