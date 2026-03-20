<?php

namespace App\Console\Commands;

use App\Support\SchemaGeneration\SchemaArtifactGenerator;
use Illuminate\Console\Command;

class GenerateSchemaArtifacts extends Command
{
    protected $signature = 'schema:generate-migrations';

    protected $description = 'Generate Laravel migrations and seeders from the LocalDatabaseSchema JSON files.';

    public function __construct(private readonly SchemaArtifactGenerator $generator)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $generated = $this->generator->generate();

        $this->info('Generated migration files:');
        foreach ($generated['migrations'] as $migration) {
            $this->line(' - '.$migration);
        }

        $this->info('Generated seeder files:');
        foreach ($generated['seeders'] as $seeder) {
            $this->line(' - '.$seeder);
        }

        return self::SUCCESS;
    }
}
