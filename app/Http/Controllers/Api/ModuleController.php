<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class ModuleController extends Controller
{
    public function __invoke(string $module): JsonResponse|Response
    {
        if ($module === 'system') {
            return response('pong', 200)
                ->header('Content-Type', 'text/plain; charset=UTF-8');
        }

        return response()->json([
            'module' => $module,
            'message' => sprintf('The %s API stub endpoint is ready for implementation.', $module),
            'status' => 'stub',
        ]);
    }
}
