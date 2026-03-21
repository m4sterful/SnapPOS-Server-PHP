<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Support\SchemaGeneration\DatabaseSchemaValidator;
use App\Support\SchemaGeneration\SchemaArtifactGenerator;
use Illuminate\Http\JsonResponse;

class SystemSchemaApplyController extends Controller
{
    public function __invoke(
        DatabaseSchemaValidator $databaseSchemaValidator,
        SchemaArtifactGenerator $schemaArtifactGenerator,
    ): JsonResponse {
        $validation = $databaseSchemaValidator->validate();
        $artifactsRegenerated = ! ($validation['valid'] ?? false);
        $generatedArtifacts = $artifactsRegenerated
            ? $schemaArtifactGenerator->generate()
            : ['migrations' => [], 'seeders' => []];

        return response()->json([
            'schema_aligned' => $validation['valid'] ?? false,
            'artifacts_regenerated' => $artifactsRegenerated,
            'message' => $artifactsRegenerated
                ? 'Generated schema migration artifacts to help re-align the database with the JSON schema.'
                : 'Database schema already matches the JSON schema; no migration artifacts were regenerated.',
            'generated_artifacts' => $generatedArtifacts,
            'validation' => $validation,
        ]);
    }
}
