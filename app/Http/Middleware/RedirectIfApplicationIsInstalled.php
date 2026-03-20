<?php

namespace App\Http\Middleware;

use App\Support\Setup\InstallationStatus;
use Closure;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RedirectIfApplicationIsInstalled
{
    public function __construct(private readonly InstallationStatus $installationStatus) {}

    public function handle(Request $request, Closure $next): Response
    {
        if ($this->installationStatus->installed()) {
            if ($request->expectsJson()) {
                return new JsonResponse([
                    'message' => 'Application has already been installed.',
                    'api_url' => route('home'),
                ], 409);
            }

            throw new NotFoundHttpException();
        }

        return $next($request);
    }
}
