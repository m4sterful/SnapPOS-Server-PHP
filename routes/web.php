<?php

use App\Http\Controllers\HomeController;
use App\Http\Controllers\InstallerController;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index']);
Route::post('/install', [InstallerController::class, 'store']);
