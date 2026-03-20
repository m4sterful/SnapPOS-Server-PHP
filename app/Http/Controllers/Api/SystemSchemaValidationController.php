<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Support\SchemaGeneration\DatabaseSchemaValidator;
use Illuminate\Http\JsonResponse;

class SystemSchemaValidationController extends Controller
{
    public function __invoke(DatabaseSchemaValidator $databaseSchemaValidator): JsonResponse
    {
        return response()->json($databaseSchemaValidator->validate());
    }
}
