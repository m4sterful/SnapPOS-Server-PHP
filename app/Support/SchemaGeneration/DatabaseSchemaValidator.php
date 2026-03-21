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
            $expectedForeignKeys = $this->normalizeExpectedForeignKeys($table['definition']['foreign_keys'] ?? []);

            $report = [
                'table' => $tableName,
                'schema_file' => $schemaBasePath.'/'.$table['file'],
                'exists' => Schema::hasTable($tableName),
                'missing_columns' => [],
                'unexpected_columns' => [],
                'type_mismatches' => [],
                'nullability_mismatches' => [],
                'missing_foreign_keys' => [],
                'unexpected_foreign_keys' => [],
                'foreign_key_name_mismatches' => [],
                'foreign_key_referenced_table_mismatches' => [],
                'foreign_key_referenced_column_mismatches' => [],
                'foreign_key_on_delete_mismatches' => [],
                'foreign_key_on_update_mismatches' => [],
                'foreign_key_notes' => [],
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

            $actualForeignKeys = $this->describeForeignKeys($connection, $databaseName, $tableName);
            $this->appendForeignKeyIssues($connection, $tableName, $expectedForeignKeys, $actualForeignKeys, $report, $issues);

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
     * @return array<int, array{name: string|null, columns: array<int, string>, referenced_table: string, referenced_columns: array<int, string>, on_delete: string|null, on_update: string|null}>
     */
    protected function describeForeignKeys(string $connection, string $databaseName, string $tableName): array
    {
        return match ($connection) {
            'sqlite' => $this->describeSqliteForeignKeys($tableName),
            'mysql', 'mariadb' => $this->describeMysqlForeignKeys($databaseName, $tableName),
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
     * @return array<int, array{name: string|null, columns: array<int, string>, referenced_table: string, referenced_columns: array<int, string>, on_delete: string|null, on_update: string|null}>
     */
    protected function describeSqliteForeignKeys(string $tableName): array
    {
        $rows = DB::select("PRAGMA foreign_key_list('".str_replace("'", "''", $tableName)."')");
        $constraints = [];

        foreach ($rows as $row) {
            $groupKey = (string) $row->id;

            if (! array_key_exists($groupKey, $constraints)) {
                $constraints[$groupKey] = [
                    'name' => null,
                    'columns' => [],
                    'referenced_table' => (string) $row->table,
                    'referenced_columns' => [],
                    'on_delete' => $this->normalizeForeignKeyAction($row->on_delete ?? null),
                    'on_update' => $this->normalizeForeignKeyAction($row->on_update ?? null),
                ];
            }

            $constraints[$groupKey]['columns'][(int) $row->seq] = (string) $row->from;
            $constraints[$groupKey]['referenced_columns'][(int) $row->seq] = (string) $row->to;
        }

        foreach ($constraints as &$constraint) {
            ksort($constraint['columns']);
            ksort($constraint['referenced_columns']);
            $constraint['columns'] = array_values($constraint['columns']);
            $constraint['referenced_columns'] = array_values($constraint['referenced_columns']);
        }
        unset($constraint);

        return array_values($constraints);
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

    /**
     * @return array<int, array{name: string|null, columns: array<int, string>, referenced_table: string, referenced_columns: array<int, string>, on_delete: string|null, on_update: string|null}>
     */
    protected function describeMysqlForeignKeys(string $databaseName, string $tableName): array
    {
        $rows = DB::select(
            <<<'SQL'
SELECT
    kcu.CONSTRAINT_NAME,
    kcu.COLUMN_NAME,
    kcu.REFERENCED_TABLE_NAME,
    kcu.REFERENCED_COLUMN_NAME,
    kcu.ORDINAL_POSITION,
    rc.DELETE_RULE,
    rc.UPDATE_RULE
FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE AS kcu
INNER JOIN INFORMATION_SCHEMA.REFERENTIAL_CONSTRAINTS AS rc
    ON rc.CONSTRAINT_SCHEMA = kcu.CONSTRAINT_SCHEMA
    AND rc.TABLE_NAME = kcu.TABLE_NAME
    AND rc.CONSTRAINT_NAME = kcu.CONSTRAINT_NAME
WHERE kcu.TABLE_SCHEMA = ?
    AND kcu.TABLE_NAME = ?
    AND kcu.REFERENCED_TABLE_NAME IS NOT NULL
ORDER BY kcu.CONSTRAINT_NAME ASC, kcu.ORDINAL_POSITION ASC
SQL,
            [$databaseName, $tableName],
        );

        $constraints = [];

        foreach ($rows as $row) {
            $groupKey = (string) $row->CONSTRAINT_NAME;

            if (! array_key_exists($groupKey, $constraints)) {
                $constraints[$groupKey] = [
                    'name' => (string) $row->CONSTRAINT_NAME,
                    'columns' => [],
                    'referenced_table' => (string) $row->REFERENCED_TABLE_NAME,
                    'referenced_columns' => [],
                    'on_delete' => $this->normalizeForeignKeyAction($row->DELETE_RULE ?? null),
                    'on_update' => $this->normalizeForeignKeyAction($row->UPDATE_RULE ?? null),
                ];
            }

            $position = max(((int) $row->ORDINAL_POSITION) - 1, 0);
            $constraints[$groupKey]['columns'][$position] = (string) $row->COLUMN_NAME;
            $constraints[$groupKey]['referenced_columns'][$position] = (string) $row->REFERENCED_COLUMN_NAME;
        }

        foreach ($constraints as &$constraint) {
            ksort($constraint['columns']);
            ksort($constraint['referenced_columns']);
            $constraint['columns'] = array_values($constraint['columns']);
            $constraint['referenced_columns'] = array_values($constraint['referenced_columns']);
        }
        unset($constraint);

        return array_values($constraints);
    }

    /**
     * @param  array<int, array<string, mixed>>  $foreignKeys
     * @return array<int, array{name: string|null, columns: array<int, string>, referenced_table: string, referenced_columns: array<int, string>, on_delete: string|null, on_update: string|null}>
     */
    protected function normalizeExpectedForeignKeys(array $foreignKeys): array
    {
        return array_values(array_map(function (array $foreignKey): array {
            return [
                'name' => $this->normalizeForeignKeyName($foreignKey['Name'] ?? $foreignKey['name'] ?? null),
                'columns' => $this->normalizeForeignKeyColumns($foreignKey['Columns'] ?? $foreignKey['columns'] ?? []),
                'referenced_table' => (string) ($foreignKey['ReferencedTable'] ?? $foreignKey['referencedTable'] ?? ''),
                'referenced_columns' => $this->normalizeForeignKeyColumns($foreignKey['ReferencedColumns'] ?? $foreignKey['referencedColumns'] ?? []),
                'on_delete' => $this->normalizeForeignKeyAction($foreignKey['OnDelete'] ?? $foreignKey['onDelete'] ?? null),
                'on_update' => $this->normalizeForeignKeyAction($foreignKey['OnUpdate'] ?? $foreignKey['onUpdate'] ?? null),
            ];
        }, $foreignKeys));
    }

    /**
     * @param  array<int, array{name: string|null, columns: array<int, string>, referenced_table: string, referenced_columns: array<int, string>, on_delete: string|null, on_update: string|null}>  $expectedForeignKeys
     * @param  array<int, array{name: string|null, columns: array<int, string>, referenced_table: string, referenced_columns: array<int, string>, on_delete: string|null, on_update: string|null}>  $actualForeignKeys
     * @param  array<string, mixed>  &$report
     * @param  array<int, array<string, mixed>>  &$issues
     */
    protected function appendForeignKeyIssues(
        string $connection,
        string $tableName,
        array $expectedForeignKeys,
        array $actualForeignKeys,
        array &$report,
        array &$issues,
    ): void {
        if ($connection === 'sqlite' && $expectedForeignKeys !== []) {
            $report['foreign_key_notes'][] = 'SQLite PRAGMA foreign_key_list does not expose constraint names, so name mismatches cannot be validated on SQLite connections.';
        }

        $actualQueue = [];
        foreach ($actualForeignKeys as $foreignKey) {
            $actualQueue[$this->foreignKeyColumnsKey($foreignKey['columns'])][] = $foreignKey;
        }

        foreach ($expectedForeignKeys as $expectedForeignKey) {
            $columnsKey = $this->foreignKeyColumnsKey($expectedForeignKey['columns']);
            $actualForeignKey = null;

            if (isset($actualQueue[$columnsKey]) && $actualQueue[$columnsKey] !== []) {
                $actualForeignKey = array_shift($actualQueue[$columnsKey]);
            }

            if ($actualForeignKey === null) {
                $report['missing_foreign_keys'][] = $this->formatForeignKeyForReport($expectedForeignKey);
                $issues[] = [
                    'table' => $tableName,
                    'foreign_key' => $this->formatForeignKeyForReport($expectedForeignKey),
                    'issue' => 'missing_foreign_key',
                    'message' => sprintf(
                        'Database foreign key [%s] on table [%s] is missing.',
                        $this->foreignKeyDisplayName($expectedForeignKey),
                        $tableName,
                    ),
                ];

                continue;
            }

            if (($actualForeignKey['name'] ?? null) !== null && ($expectedForeignKey['name'] ?? null) !== null && $actualForeignKey['name'] !== $expectedForeignKey['name']) {
                $report['foreign_key_name_mismatches'][] = [
                    'columns' => $expectedForeignKey['columns'],
                    'expected' => $expectedForeignKey['name'],
                    'actual' => $actualForeignKey['name'],
                ];
                $issues[] = [
                    'table' => $tableName,
                    'columns' => $expectedForeignKey['columns'],
                    'issue' => 'foreign_key_name_mismatch',
                    'message' => sprintf(
                        'Database foreign key on [%s] has name [%s] but schema expects [%s].',
                        implode(', ', $expectedForeignKey['columns']),
                        $actualForeignKey['name'],
                        $expectedForeignKey['name'],
                    ),
                ];
            }

            if ($actualForeignKey['referenced_table'] !== $expectedForeignKey['referenced_table']) {
                $report['foreign_key_referenced_table_mismatches'][] = [
                    'columns' => $expectedForeignKey['columns'],
                    'expected' => $expectedForeignKey['referenced_table'],
                    'actual' => $actualForeignKey['referenced_table'],
                ];
                $issues[] = [
                    'table' => $tableName,
                    'columns' => $expectedForeignKey['columns'],
                    'issue' => 'foreign_key_referenced_table_mismatch',
                    'message' => sprintf(
                        'Database foreign key [%s] on table [%s] references table [%s] but schema expects [%s].',
                        $this->foreignKeyDisplayName($expectedForeignKey),
                        $tableName,
                        $actualForeignKey['referenced_table'],
                        $expectedForeignKey['referenced_table'],
                    ),
                ];
            }

            if ($actualForeignKey['referenced_columns'] !== $expectedForeignKey['referenced_columns']) {
                $report['foreign_key_referenced_column_mismatches'][] = [
                    'columns' => $expectedForeignKey['columns'],
                    'expected' => $expectedForeignKey['referenced_columns'],
                    'actual' => $actualForeignKey['referenced_columns'],
                ];
                $issues[] = [
                    'table' => $tableName,
                    'columns' => $expectedForeignKey['columns'],
                    'issue' => 'foreign_key_referenced_column_mismatch',
                    'message' => sprintf(
                        'Database foreign key [%s] on table [%s] references columns [%s] but schema expects [%s].',
                        $this->foreignKeyDisplayName($expectedForeignKey),
                        $tableName,
                        implode(', ', $actualForeignKey['referenced_columns']),
                        implode(', ', $expectedForeignKey['referenced_columns']),
                    ),
                ];
            }

            if ($actualForeignKey['on_delete'] !== $expectedForeignKey['on_delete']) {
                $report['foreign_key_on_delete_mismatches'][] = [
                    'columns' => $expectedForeignKey['columns'],
                    'expected' => $expectedForeignKey['on_delete'],
                    'actual' => $actualForeignKey['on_delete'],
                ];
                $issues[] = [
                    'table' => $tableName,
                    'columns' => $expectedForeignKey['columns'],
                    'issue' => 'foreign_key_on_delete_mismatch',
                    'message' => sprintf(
                        'Database foreign key [%s] on table [%s] has ON DELETE [%s] but schema expects [%s].',
                        $this->foreignKeyDisplayName($expectedForeignKey),
                        $tableName,
                        $actualForeignKey['on_delete'] ?? 'null',
                        $expectedForeignKey['on_delete'] ?? 'null',
                    ),
                ];
            }

            if ($actualForeignKey['on_update'] !== $expectedForeignKey['on_update']) {
                $report['foreign_key_on_update_mismatches'][] = [
                    'columns' => $expectedForeignKey['columns'],
                    'expected' => $expectedForeignKey['on_update'],
                    'actual' => $actualForeignKey['on_update'],
                ];
                $issues[] = [
                    'table' => $tableName,
                    'columns' => $expectedForeignKey['columns'],
                    'issue' => 'foreign_key_on_update_mismatch',
                    'message' => sprintf(
                        'Database foreign key [%s] on table [%s] has ON UPDATE [%s] but schema expects [%s].',
                        $this->foreignKeyDisplayName($expectedForeignKey),
                        $tableName,
                        $actualForeignKey['on_update'] ?? 'null',
                        $expectedForeignKey['on_update'] ?? 'null',
                    ),
                ];
            }
        }

        foreach ($actualQueue as $remainingForeignKeys) {
            foreach ($remainingForeignKeys as $actualForeignKey) {
                $report['unexpected_foreign_keys'][] = $this->formatForeignKeyForReport($actualForeignKey);
                $issues[] = [
                    'table' => $tableName,
                    'foreign_key' => $this->formatForeignKeyForReport($actualForeignKey),
                    'issue' => 'unexpected_foreign_key',
                    'message' => sprintf(
                        'Database table [%s] has unexpected foreign key [%s].',
                        $tableName,
                        $this->foreignKeyDisplayName($actualForeignKey),
                    ),
                ];
            }
        }
    }

    /**
     * @param  array<int, string>  $columns
     */
    protected function foreignKeyColumnsKey(array $columns): string
    {
        return implode('|', $columns);
    }

    /**
     * @param  array{name: string|null, columns: array<int, string>, referenced_table: string, referenced_columns: array<int, string>, on_delete: string|null, on_update: string|null}  $foreignKey
     * @return array<string, mixed>
     */
    protected function formatForeignKeyForReport(array $foreignKey): array
    {
        return [
            'name' => $foreignKey['name'],
            'columns' => $foreignKey['columns'],
            'referenced_table' => $foreignKey['referenced_table'],
            'referenced_columns' => $foreignKey['referenced_columns'],
            'on_delete' => $foreignKey['on_delete'],
            'on_update' => $foreignKey['on_update'],
        ];
    }

    /**
     * @param  array{name: string|null, columns: array<int, string>, referenced_table: string, referenced_columns: array<int, string>, on_delete: string|null, on_update: string|null}  $foreignKey
     */
    protected function foreignKeyDisplayName(array $foreignKey): string
    {
        if (($foreignKey['name'] ?? null) !== null && $foreignKey['name'] !== '') {
            return $foreignKey['name'];
        }

        return implode(', ', $foreignKey['columns']).' -> '.$foreignKey['referenced_table'].'('.implode(', ', $foreignKey['referenced_columns']).')';
    }

    /**
     * @param  mixed  $name
     */
    protected function normalizeForeignKeyName(mixed $name): ?string
    {
        $normalized = trim((string) $name);

        return $normalized === '' ? null : $normalized;
    }

    /**
     * @param  mixed  $action
     */
    protected function normalizeForeignKeyAction(mixed $action): ?string
    {
        $normalized = strtoupper(trim((string) $action));

        return $normalized === '' ? null : $normalized;
    }

    /**
     * @param  iterable<mixed>  $columns
     * @return array<int, string>
     */
    protected function normalizeForeignKeyColumns(iterable $columns): array
    {
        $normalized = [];

        foreach ($columns as $column) {
            $normalized[] = (string) $column;
        }

        return array_values($normalized);
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
