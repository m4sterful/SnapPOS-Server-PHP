<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

class HomeController extends Controller
{
    public function index(): View
    {
        return view('status', [
            'connection' => config('database.default'),
            'database' => config('database.connections.'.config('database.default').'.database'),
        ]);
    }
}
