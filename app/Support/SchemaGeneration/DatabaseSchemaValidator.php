<?php

namespace App\Support\SchemaGeneration;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

class DatabaseSchemaValidator
{
    public function __construct(private readonly LocalSchemaManifest $manifest) {}

    /**
     * @return array<string, mixed>
     */
    public function validate(): array
    {
        $schema = $this->manifest->load();
        $connection = (string) config('database.default');
        $databaseName = (string) config("database.connections.{$connection}.database");
        $schemaBasePath = $this->manifest->basePath();
        $manifestPath = $schemaBasePath.'/schema.json';

        $tableReports = [];
        $issues = [];

        foreach ($schema['tables'] as $table) {
            $tableName = (string) $table['table_name'];
            $expectedColumns = collect($table['definition']['columns'])
                ->mapWithKeys(fn (array $column): array => [(string) ($column['Name'] ?? $column['name']) => $column])
                ->all();

            $report = [
                'table' => $tableName,
                'schema_file' => $schemaBasePath.'/'.$table['file'],
                'exists' => Schema::hasTable($tableName),
                'missing_columns' => [],
                'unexpected_columns' => [],
                'type_mismatches' => [],
                'nullability_mismatches' => [],
            ];

            if (! $report['exists']) {
                $issues[] = [
                    'table' => $tableName,
                    'issue' => 'missing_table',
                    'message' => "Database table [{$tableName}] is missing.",
                ];

                $tableReports[] = $report;

                continue;
            }

            $actualColumns = $this->describeTable($connection, $databaseName, $tableName);

            foreach ($expectedColumns as $columnName => $column) {
                if (! array_key_exists($columnName, $actualColumns)) {
                    $report['missing_columns'][] = $columnName;
                    $issues[] = [
                        'table' => $tableName,
                        'column' => $columnName,
                        'issue' => 'missing_column',
                        'message' => "Database column [{$tableName}.{$columnName}] is missing.",
                    ];

                    continue;
                }

                $expectedType = strtoupper((string) ($column['Type'] ?? $column['type']));
                $actualType = (string) $actualColumns[$columnName]['type'];

                if (! $this->typesMatch($expectedType, $actualType)) {
                    $report['type_mismatches'][] = [
                        'column' => $columnName,
                        'expected' => $expectedType,
                        'actual' => $actualType,
                    ];
                    $issues[] = [
                        'table' => $tableName,
                        'column' => $columnName,
                        'issue' => 'type_mismatch',
                        'message' => "Database column [{$tableName}.{$columnName}] has type [{$actualType}] but schema expects [{$expectedType}].",
                    ];
                }

                $expectedNullable = (bool) ($column['Nullable'] ?? $column['nullable'] ?? false);
                $actualNullable = (bool) $actualColumns[$columnName]['nullable'];

                if ($expectedNullable !== $actualNullable) {
                    $report['nullability_mismatches'][] = [
                        'column' => $columnName,
                        'expected' => $expectedNullable,
                        'actual' => $actualNullable,
                    ];
                    $issues[] = [
                        'table' => $tableName,
                        'column' => $columnName,
                        'issue' => 'nullability_mismatch',
                        'message' => sprintf(
                            'Database column [%s.%s] nullable=%s but schema expects nullable=%s.',
                            $tableName,
                            $columnName,
                            $actualNullable ? 'true' : 'false',
                            $expectedNullable ? 'true' : 'false',
                        ),
                    ];
                }
            }

            $report['unexpected_columns'] = array_values(array_diff(array_keys($actualColumns), array_keys($expectedColumns), ['id']));
            $tableReports[] = $report;
        }

        return [
            'valid' => $issues === [],
            'schema_base_path' => $schemaBasePath,
            'manifest_path' => $manifestPath,
            'path_source' => LocalSchemaManifest::class.'::basePath',
            'database' => [
                'connection' => $connection,
                'name' => $databaseName,
            ],
            'summary' => [
                'expected_table_count' => count($schema['tables']),
                'expected_seed_count' => count($schema['seeds']),
                'issue_count' => count($issues),
            ],
            'tables' => $tableReports,
            'issues' => $issues,
        ];
    }

    /**
     * @return array<string, array{type: string, nullable: bool}>
     */
    protected function describeTable(string $connection, string $databaseName, string $tableName): array
    {
        return match ($connection) {
            'sqlite' => $this->describeSqliteTable($tableName),
            'mysql', 'mariadb' => $this->describeMysqlTable($databaseName, $tableName),
            default => throw new RuntimeException("Unsupported connection [{$connection}] for schema validation."),
        };
    }

    /**
     * @return array<string, array{type: string, nullable: bool}>
     */
    protected function describeSqliteTable(string $tableName): array
    {
        $rows = DB::select("PRAGMA table_info('".str_replace("'", "''", $tableName)."')");
        $columns = [];

        foreach ($rows as $row) {
            $columns[(string) $row->name] = [
                'type' => strtoupper((string) $row->type),
                'nullable' => ((int) $row->notnull) === 0,
            ];
        }

        return $columns;
    }

    /**
     * @return array<string, array{type: string, nullable: bool}>
     */
    protected function describeMysqlTable(string $databaseName, string $tableName): array
    {
        $rows = DB::select(
            'SELECT COLUMN_NAME, COLUMN_TYPE, IS_NULLABLE FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? ORDER BY ORDINAL_POSITION',
            [$databaseName, $tableName],
        );
        $columns = [];

        foreach ($rows as $row) {
            $columns[(string) $row->COLUMN_NAME] = [
                'type' => strtoupper((string) $row->COLUMN_TYPE),
                'nullable' => strtoupper((string) $row->IS_NULLABLE) === 'YES',
            ];
        }

        return $columns;
    }

    protected function typesMatch(string $expectedType, string $actualType): bool
    {
        $expected = $this->canonicalizeType($expectedType);
        $actual = $this->canonicalizeType($actualType);

        return $expected === $actual;
    }

    protected function canonicalizeType(string $type): string
    {
        $normalized = strtoupper(trim($type));

        return match (true) {
            str_starts_with($normalized, 'VARCHAR('), str_starts_with($normalized, 'CHAR('), $normalized === 'VARCHAR', $normalized === 'CHAR' => 'STRING',
            str_starts_with($normalized, 'DECIMAL('), str_starts_with($normalized, 'NUMERIC('), $normalized === 'NUMERIC', $normalized === 'DECIMAL' => 'DECIMAL',
            str_contains($normalized, 'INT') => 'INT',
            str_contains($normalized, 'TEXT'), str_contains($normalized, 'CLOB') => 'TEXT',
            str_contains($normalized, 'DATETIME'), str_contains($normalized, 'TIMESTAMP') => 'DATETIME',
            $normalized === 'DATE' => 'DATE',
            str_contains($normalized, 'DOUBLE') => 'FLOATING_POINT',
            str_contains($normalized, 'REAL') => 'FLOATING_POINT',
            str_contains($normalized, 'FLOAT') => 'FLOATING_POINT',
            default => $normalized,
        };
    }
}
