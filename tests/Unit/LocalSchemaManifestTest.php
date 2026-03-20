<?php

namespace Tests\Unit;

use App\Support\SchemaGeneration\LocalSchemaManifest;
use Tests\TestCase;

class LocalSchemaManifestTest extends TestCase
{
    public function test_manifest_base_path_points_at_reference_local_database_schema(): void
    {
        $manifest = app(LocalSchemaManifest::class);

        $this->assertSame(base_path('reference/LocalDatabaseSchema'), $manifest->basePath());
    }

    public function test_manifest_is_normalized_from_reference_json(): void
    {
        $manifest = app(LocalSchemaManifest::class)->load();

        $this->assertNotEmpty($manifest['tables']);
        $this->assertNotEmpty($manifest['seeds']);
        $this->assertSame('sys', $manifest['tables'][0]['table_name']);
        $this->assertArrayHasKey('definition', $manifest['tables'][0]);
        $this->assertArrayHasKey('columns', $manifest['tables'][0]['definition']);
        $this->assertSame('ensure_missing_rows', $manifest['seeds'][0]['definition']['mode']);
    }
}
