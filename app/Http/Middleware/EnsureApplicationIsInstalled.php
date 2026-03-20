<?php

namespace App\Http\Middleware;

use App\Support\Setup\InstallationStatus;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureApplicationIsInstalled
{
    public function __construct(private readonly InstallationStatus $installationStatus) {}

    public function handle(Request $request, Closure $next): Response
    {
        if (! $this->installationStatus->installed()) {
            return new JsonResponse([
                'message' => 'Application setup is required before API access is available.',
                'setup_url' => route('setup.show'),
            ], 409);
        }

        return $next($request);
    }
}
