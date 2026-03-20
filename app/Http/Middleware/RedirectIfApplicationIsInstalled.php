<?php

namespace App\Http\Middleware;

use App\Support\Setup\InstallationStatus;
use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RedirectIfApplicationIsInstalled
{
    public function __construct(private readonly InstallationStatus $installationStatus) {}

    public function handle(Request $request, Closure $next): Response
    {
        if ($this->installationStatus->installed()) {
            return new RedirectResponse(route('home'));
        }

        return $next($request);
    }
}
