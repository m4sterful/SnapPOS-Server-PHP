<?php

namespace Tests\Unit;

use App\Support\SchemaGeneration\SchemaArtifactGenerator;
use Tests\TestCase;

class SchemaArtifactGeneratorTest extends TestCase
{
    public function test_generator_creates_deterministic_migration_and_seeder_files(): void
    {
        $generated = app(SchemaArtifactGenerator::class)->generate();

        $this->assertContains('database/seeders/GeneratedLocalSchemaSeeder.php', $generated['seeders']);
        $this->assertFileExists(base_path('database/seeders/GeneratedLocalSchemaSeeder.php'));
        $this->assertFileExists(base_path('database/migrations/2026_01_01_000001_create_generated_local_schema_sys_table.php'));
        $this->assertFileExists(base_path('database/migrations/2026_01_01_000006_create_generated_local_schema_users_table.php'));
        $this->assertStringContainsString(
            'class GeneratedLocalSchemaSeeder extends Seeder',
            (string) file_get_contents(base_path('database/seeders/GeneratedLocalSchemaSeeder.php'))
        );
    }
}
