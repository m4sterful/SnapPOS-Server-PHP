<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class ApplicationStatusController extends Controller
{
    public function __invoke(): JsonResponse
    {
        $connection = (string) config('database.default');

        return response()->json([
            'message' => 'SnapPOS Server API is available.',
            'application' => config('app.name'),
            'database' => [
                'connection' => $connection,
                'name' => config("database.connections.{$connection}.database"),
            ],
        ]);
    }
}
