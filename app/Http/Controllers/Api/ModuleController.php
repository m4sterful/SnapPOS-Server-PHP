<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class ModuleController extends Controller
{
    public function __invoke(string $module): JsonResponse
    {
        return response()->json([
            'module' => $module,
            'message' => sprintf('The %s API stub endpoint is ready for implementation.', $module),
            'status' => 'stub',
        ]);
    }
}
