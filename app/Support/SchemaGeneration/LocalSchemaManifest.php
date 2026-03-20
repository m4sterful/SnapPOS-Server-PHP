<?php

namespace App\Support\SchemaGeneration;

use Illuminate\Support\Arr;
use RuntimeException;

class LocalSchemaManifest
{
    public function basePath(): string
    {
        return base_path('reference/LocalDatabaseSchema');
    }

    /**
     * @return array{tables: array<int, array<string, mixed>>, seeds: array<int, array<string, mixed>>}
     */
    public function load(): array
    {
        $manifest = $this->loadJson($this->basePath().'/schema.json');

        return [
            'tables' => array_map(fn (array $entry): array => $this->normalizeTable($entry), Arr::wrap($manifest['Tables'] ?? $manifest['tables'] ?? [])),
            'seeds' => array_map(fn (array $entry): array => $this->normalizeSeed($entry), Arr::wrap($manifest['Seeds'] ?? $manifest['seeds'] ?? [])),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function loadTableDefinition(string $relativePath): array
    {
        return $this->loadJson($this->basePath().'/'.$relativePath);
    }

    /**
     * @return array<string, mixed>
     */
    public function loadSeedDefinition(string $relativePath): array
    {
        return $this->loadJson($this->basePath().'/'.$relativePath);
    }

    /**
     * @return array<string, mixed>
     */
    protected function loadJson(string $path): array
    {
        if (! is_file($path)) {
            throw new RuntimeException(sprintf('Schema file not found: %s', $path));
        }

        $decoded = json_decode($this->stripBom((string) file_get_contents($path)), true);

        if (! is_array($decoded)) {
            throw new RuntimeException(sprintf('Schema file is not valid JSON: %s', $path));
        }

        return $decoded;
    }

    protected function stripBom(string $contents): string
    {
        return preg_replace('/^\xEF\xBB\xBF/', '', $contents) ?? $contents;
    }

    /**
     * @param  array<string, mixed>  $entry
     * @return array<string, mixed>
     */
    protected function normalizeTable(array $entry): array
    {
        $definition = $this->loadTableDefinition((string) ($entry['File'] ?? $entry['file']));

        return [
            'table_name' => (string) ($entry['TableName'] ?? $entry['tableName']),
            'version' => (int) ($entry['Version'] ?? $entry['version'] ?? 1),
            'version_key_name' => (string) ($entry['VersionKeyName'] ?? $entry['versionKeyName'] ?? $entry['TableName'] ?? $entry['tableName']),
            'file' => (string) ($entry['File'] ?? $entry['file']),
            'definition' => [
                'table_name' => (string) ($definition['TableName'] ?? $definition['tableName']),
                'columns' => array_values(Arr::wrap($definition['Columns'] ?? $definition['columns'] ?? [])),
                'indexes' => array_values(Arr::wrap($definition['Indexes'] ?? $definition['indexes'] ?? [])),
                'unique_indexes' => array_values(Arr::wrap($definition['UniqueIndexes'] ?? $definition['uniqueIndexes'] ?? [])),
                'foreign_keys' => array_values(Arr::wrap($definition['ForeignKeys'] ?? $definition['foreignKeys'] ?? [])),
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $entry
     * @return array<string, mixed>
     */
    protected function normalizeSeed(array $entry): array
    {
        $definition = $this->loadSeedDefinition((string) ($entry['File'] ?? $entry['file']));

        return [
            'seed_name' => (string) ($entry['SeedName'] ?? $entry['seedName']),
            'version' => (int) ($entry['Version'] ?? $entry['version'] ?? 1),
            'version_key_name' => (string) ($entry['VersionKeyName'] ?? $entry['versionKeyName'] ?? $entry['SeedName'] ?? $entry['seedName']),
            'file' => (string) ($entry['File'] ?? $entry['file']),
            'definition' => [
                'table_name' => (string) ($definition['TableName'] ?? $definition['tableName']),
                'mode' => (string) ($definition['Mode'] ?? $definition['mode'] ?? 'ensure_missing_rows'),
                'match_columns' => array_values(Arr::wrap($definition['MatchColumns'] ?? $definition['matchColumns'] ?? [])),
                'patch_columns_when_empty' => array_values(Arr::wrap($definition['PatchColumnsWhenEmpty'] ?? $definition['patchColumnsWhenEmpty'] ?? [])),
                'zero_is_empty_columns' => array_values(Arr::wrap($definition['ZeroIsEmptyColumns'] ?? $definition['zeroIsEmptyColumns'] ?? [])),
                'rows' => array_values(Arr::wrap($definition['Rows'] ?? $definition['rows'] ?? [])),
            ],
        ];
    }
}
