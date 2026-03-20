<?php

namespace App\Support;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use PDO;
use RuntimeException;
use Throwable;

class LocalSchemaManager
{
    /**
     * @param  array<string, mixed>  $config
     * @return array{created: array<int, string>, updated: array<int, string>, seeded: array<int, string>, warnings: array<int, string>, connection: string}
     */
    public function synchronize(array $config): array
    {
        $manifest = $this->loadJson($this->schemaBasePath().'/schema.json');
        $tables = $this->arrayValue($manifest, 'Tables', []);
        $seedManifest = $this->arrayValue($manifest, 'Seeds', []);

        $result = [
            'created' => [],
            'updated' => [],
            'seeded' => [],
            'warnings' => [],
            'connection' => sprintf('%s:%s/%s', $config['db_host'], $config['db_port'], $config['db_name']),
        ];

        $this->createDatabaseIfMissing($config);
        $this->configureLaravelConnection($config);
        DB::connection('mysql')->getPdo();

        foreach ($tables as $tableManifest) {
            $definition = $this->loadJson($this->schemaBasePath().'/'.$this->stringValue($tableManifest, 'File'));
            $name = $this->prefixedTableName($config, $this->stringValue($definition, 'TableName', $this->stringValue($tableManifest, 'TableName')));

            if (! $this->tableExists($name)) {
                DB::statement($this->buildCreateTableSql($name, $definition));
                $result['created'][] = sprintf('Created table %s', $name);
            }
        }

        foreach ($tables as $tableManifest) {
            $definition = $this->loadJson($this->schemaBasePath().'/'.$this->stringValue($tableManifest, 'File'));
            $baseTableName = $this->stringValue($definition, 'TableName', $this->stringValue($tableManifest, 'TableName'));
            $name = $this->prefixedTableName($config, $baseTableName);
            $versionKey = $this->stringValue($tableManifest, 'VersionKeyName', $baseTableName);
            $version = (int) $this->arrayValue($tableManifest, 'Version', 1);

            $changes = $this->syncTable($name, $definition);
            foreach ($changes as $change) {
                $result['updated'][] = $change;
            }

            $this->setSysValue($config, $versionKey, (string) $version);
        }

        foreach ($tables as $tableManifest) {
            $definition = $this->loadJson($this->schemaBasePath().'/'.$this->stringValue($tableManifest, 'File'));
            $baseTableName = $this->stringValue($definition, 'TableName', $this->stringValue($tableManifest, 'TableName'));
            $name = $this->prefixedTableName($config, $baseTableName);

            $changes = [];
            $this->syncForeignKeys($config, $name, $this->arrayValue($definition, 'ForeignKeys', []), $changes);

            foreach ($changes as $change) {
                $result['updated'][] = $change;
            }
        }

        if ((bool) ($config['seed_test_data'] ?? false)) {
            $pendingSeeds = [];
            $customSeedPath = $this->schemaBasePath().'/seeds/'.$config['db_name'].'.seed.json';

            if (is_file($customSeedPath)) {
                foreach ($this->normalizeSeedPayload($this->loadJson($customSeedPath), $config['db_name']) as $seedName => $seedDefinition) {
                    $pendingSeeds[] = [
                        'label' => sprintf('custom seed %s', $seedName),
                        'name' => $seedName,
                        'version' => 1,
                        'definition' => $seedDefinition,
                    ];
                }
            }

            foreach ($seedManifest as $seedDefinition) {
                $file = $this->schemaBasePath().'/'.$this->stringValue($seedDefinition, 'File');
                $pendingSeeds[] = [
                    'label' => sprintf('seed %s', $this->stringValue($seedDefinition, 'VersionKeyName', $this->stringValue($seedDefinition, 'SeedName', basename($file)))),
                    'name' => $this->stringValue($seedDefinition, 'VersionKeyName', $this->stringValue($seedDefinition, 'SeedName', basename($file))),
                    'version' => (int) $this->arrayValue($seedDefinition, 'Version', 1),
                    'definition' => $this->loadJson($file),
                ];
            }

            $pendingSeeds = $this->applySeedsWithRetry($config, $pendingSeeds, $result['seeded']);

            if ($pendingSeeds !== []) {
                $names = array_map(fn (array $seed): string => $seed['label'], $pendingSeeds);
                throw new RuntimeException('Unable to apply dependent seeds: '.implode(', ', $names));
            }
        }

        return $result;
    }

    /**
     * @param  array<string, mixed>  $config
     */
    public function createDatabaseIfMissing(array $config): void
    {
        $pdo = new PDO(
            sprintf('mysql:host=%s;port=%s;charset=utf8mb4', $config['db_host'], $config['db_port']),
            $config['db_user'],
            $config['db_password'],
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );

        $pdo->exec(sprintf(
            'CREATE DATABASE IF NOT EXISTS %s CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci',
            $this->quoteIdentifier($config['db_name'])
        ));
    }

    public function schemaBasePath(): string
    {
        return realpath(base_path('../Source/LocalDatabaseSchema')) ?: base_path('../Source/LocalDatabaseSchema');
    }

    /**
     * @param  array<string, mixed>  $config
     */
    public function configureLaravelConnection(array $config): void
    {
        config([
            'database.default' => 'mysql',
            'database.connections.mysql' => array_merge(config('database.connections.mysql', []), [
                'driver' => 'mysql',
                'host' => $config['db_host'],
                'port' => $config['db_port'],
                'database' => $config['db_name'],
                'username' => $config['db_user'],
                'password' => $config['db_password'],
                'prefix' => '',
                'strict' => true,
                'charset' => 'utf8mb4',
                'collation' => 'utf8mb4_unicode_ci',
            ]),
        ]);

        DB::purge('mysql');
        DB::reconnect('mysql');
    }

    /**
     * @param  array<string, mixed>  $config
     * @param  array<string, mixed>  $definition
     * @return array<int, string>
     */
    protected function syncTable(string $tableName, array $definition): array
    {
        $changes = [];
        $columns = $this->arrayValue($definition, 'Columns', []);
        $indexes = $this->arrayValue($definition, 'Indexes', []);
        $uniqueIndexes = $this->arrayValue($definition, 'UniqueIndexes', []);

        $existingColumns = $this->existingColumns($tableName);

        foreach ($columns as $column) {
            $columnName = $this->stringValue($column, 'Name');
            $columnSql = $this->buildColumnDefinition($column);

            if (! isset($existingColumns[$columnName])) {
                DB::statement(sprintf('ALTER TABLE %s ADD COLUMN %s', $this->quoteIdentifier($tableName), $columnSql));
                $changes[] = sprintf('Added column %s.%s', $tableName, $columnName);

                continue;
            }

            if ($this->columnNeedsChange($existingColumns[$columnName], $column)) {
                DB::statement(sprintf('ALTER TABLE %s MODIFY COLUMN %s', $this->quoteIdentifier($tableName), $columnSql));
                $changes[] = sprintf('Modified column %s.%s', $tableName, $columnName);
            }
        }

        $this->syncIndexes($tableName, $indexes, false, $changes);
        $this->syncIndexes($tableName, $uniqueIndexes, true, $changes);

        return $changes;
    }

    /**
     * @param  array<int, array<string, mixed>>  $indexes
     * @param  array<int, string>  $changes
     */
    protected function syncIndexes(string $tableName, array $indexes, bool $unique, array &$changes): void
    {
        $existing = $this->existingIndexes($tableName);

        foreach ($indexes as $index) {
            $indexName = $this->stringValue($index, 'Name');
            $columns = $this->arrayValue($index, 'Columns', []);

            if (isset($existing[$indexName])
                && $existing[$indexName]['unique'] === $unique
                && $existing[$indexName]['columns'] === $columns) {
                continue;
            }

            if (isset($existing[$indexName])) {
                DB::statement(sprintf('ALTER TABLE %s DROP INDEX %s', $this->quoteIdentifier($tableName), $this->quoteIdentifier($indexName)));
                $changes[] = sprintf('Rebuilt index %s on %s', $indexName, $tableName);
            } else {
                $changes[] = sprintf('Added index %s on %s', $indexName, $tableName);
            }

            $keyword = $unique ? 'ADD UNIQUE INDEX' : 'ADD INDEX';
            $columnList = implode(', ', array_map(fn (string $column): string => $this->quoteIdentifier($column), $columns));
            DB::statement(sprintf(
                'ALTER TABLE %s %s %s (%s)',
                $this->quoteIdentifier($tableName),
                $keyword,
                $this->quoteIdentifier($indexName),
                $columnList,
            ));
        }
    }

    /**
     * @param  array<int, array<string, mixed>>  $foreignKeys
     * @param  array<int, string>  $changes
     */
    protected function syncForeignKeys(array $config, string $tableName, array $foreignKeys, array &$changes): void
    {
        $existing = $this->existingForeignKeys($tableName);

        foreach ($foreignKeys as $foreignKey) {
            $name = $this->stringValue($foreignKey, 'Name');
            $columns = $this->arrayValue($foreignKey, 'Columns', []);
            $referencedTable = $this->prefixedTableName($config, $this->stringValue($foreignKey, 'ReferencedTable'));
            $referencedColumns = $this->arrayValue($foreignKey, 'ReferencedColumns', []);
            $onDelete = strtoupper($this->stringValue($foreignKey, 'OnDelete', 'RESTRICT'));
            $onUpdate = strtoupper($this->stringValue($foreignKey, 'OnUpdate', 'RESTRICT'));

            $expected = [
                'columns' => $columns,
                'referenced_table' => $referencedTable,
                'referenced_columns' => $referencedColumns,
                'on_delete' => $onDelete,
                'on_update' => $onUpdate,
            ];

            if (isset($existing[$name]) && $existing[$name] === $expected) {
                continue;
            }

            if (isset($existing[$name])) {
                DB::statement(sprintf('ALTER TABLE %s DROP FOREIGN KEY %s', $this->quoteIdentifier($tableName), $this->quoteIdentifier($name)));
                $changes[] = sprintf('Rebuilt foreign key %s on %s', $name, $tableName);
            } else {
                $changes[] = sprintf('Added foreign key %s on %s', $name, $tableName);
            }

            $columnList = implode(', ', array_map(fn (string $column): string => $this->quoteIdentifier($column), $columns));
            $referenceList = implode(', ', array_map(fn (string $column): string => $this->quoteIdentifier($column), $referencedColumns));

            DB::statement(sprintf(
                'ALTER TABLE %s ADD CONSTRAINT %s FOREIGN KEY (%s) REFERENCES %s (%s) ON DELETE %s ON UPDATE %s',
                $this->quoteIdentifier($tableName),
                $this->quoteIdentifier($name),
                $columnList,
                $this->quoteIdentifier($referencedTable),
                $referenceList,
                $onDelete,
                $onUpdate,
            ));
        }
    }

    /**
     * @param  array<string, mixed>  $config
     * @param  array<string, mixed>  $seedDefinition
     */
    /**
     * @param  array<string, mixed>  $config
     * @param  array<int, array{name: string, label: string, version: int, definition: array<string, mixed>}>  $pendingSeeds
     * @param  array<int, string>  $appliedLabels
     * @return array<int, array{name: string, label: string, version: int, definition: array<string, mixed>}>
     */
    protected function applySeedsWithRetry(array $config, array $pendingSeeds, array &$appliedLabels): array
    {
        do {
            $progress = false;
            $remaining = [];

            foreach ($pendingSeeds as $seed) {
                try {
                    if ($this->applySeedDefinition($config, $seed['name'], $seed['version'], $seed['definition'])) {
                        $appliedLabels[] = sprintf('Applied %s', $seed['label']);
                    }

                    $progress = true;
                } catch (Throwable $throwable) {
                    $remaining[] = $seed;
                }
            }

            $pendingSeeds = $remaining;
        } while ($progress && $pendingSeeds !== []);

        return $pendingSeeds;
    }

    protected function applySeedDefinition(array $config, string $seedName, int $version, array $seedDefinition): bool
    {
        $currentVersion = (int) $this->getSysValue($config, $seedName, '0');

        if ($currentVersion >= $version) {
            return false;
        }

        $tableName = $this->prefixedTableName($config, $this->stringValue($seedDefinition, 'TableName'));
        $mode = Str::lower($this->stringValue($seedDefinition, 'Mode', 'ensure_missing_rows'));
        $matchColumns = $this->arrayValue($seedDefinition, 'MatchColumns', []);
        $patchColumns = $this->arrayValue($seedDefinition, 'PatchColumnsWhenEmpty', []);
        $zeroIsEmptyColumns = $this->arrayValue($seedDefinition, 'ZeroIsEmptyColumns', []);
        $rows = $this->arrayValue($seedDefinition, 'Rows', []);

        if ($mode === 'insert_all_if_table_empty' && DB::table($tableName)->count() > 0) {
            $this->setSysValue($config, $seedName, (string) $version);

            return false;
        }

        foreach ($rows as $row) {
            $values = $this->arrayValue($row, 'Values', []);
            $lookups = $this->arrayValue($row, 'Lookups', []);
            $record = $this->resolveSeedLookups($config, $values, $lookups);

            if ($mode === 'insert_all_if_table_empty') {
                DB::table($tableName)->insert($record);

                continue;
            }

            $criteria = Arr::only($record, $matchColumns);
            $existing = ! empty($criteria) ? DB::table($tableName)->where($criteria)->first() : null;

            if ($mode === 'ensure_missing_rows') {
                if (! $existing) {
                    DB::table($tableName)->insert($record);
                }

                continue;
            }

            if ($mode === 'patch_existing_when_empty' && $existing) {
                $updates = [];

                foreach ($patchColumns as $column) {
                    $currentValue = $existing->{$column} ?? null;
                    $isEmpty = $currentValue === null || $currentValue === '' || (in_array($column, $zeroIsEmptyColumns, true) && (int) $currentValue === 0);

                    if ($isEmpty && array_key_exists($column, $record)) {
                        $updates[$column] = $record[$column];
                    }
                }

                if ($updates !== []) {
                    DB::table($tableName)->where($criteria)->update($updates);
                }
            }
        }

        $this->setSysValue($config, $seedName, (string) $version);

        return true;
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, array<string, mixed>>
     */
    protected function normalizeSeedPayload(array $payload, string $fallbackName): array
    {
        $seeds = $this->arrayValue($payload, 'Seeds', null);

        if (is_array($seeds)) {
            $normalized = [];

            foreach ($seeds as $index => $seed) {
                $normalized[$this->stringValue($seed, 'SeedName', $fallbackName.'_'.$index)] = $seed;
            }

            return $normalized;
        }

        return [$fallbackName => $payload];
    }

    /**
     * @param  array<string, mixed>  $config
     * @param  array<string, mixed>  $values
     * @param  array<int, array<string, mixed>>  $lookups
     * @return array<string, mixed>
     */
    protected function resolveSeedLookups(array $config, array $values, array $lookups): array
    {
        $record = $values;

        foreach ($lookups as $lookup) {
            $tableName = $this->prefixedTableName($config, $this->stringValue($lookup, 'LookupTable'));
            $lookupColumn = $this->stringValue($lookup, 'LookupColumn');
            $selectColumn = $this->stringValue($lookup, 'SelectColumn');
            $lookupValue = $this->arrayValue($lookup, 'LookupValue');
            $targetColumn = $this->stringValue($lookup, 'TargetColumn');
            $required = (bool) $this->arrayValue($lookup, 'Required', false);

            $value = DB::table($tableName)->where($lookupColumn, $lookupValue)->value($selectColumn);

            if ($value === null && $required) {
                throw new RuntimeException(sprintf('Lookup for %s.%s failed.', $tableName, $lookupColumn));
            }

            $record[$targetColumn] = $value;
        }

        return $record;
    }

    protected function tableExists(string $tableName): bool
    {
        $database = DB::connection('mysql')->getDatabaseName();

        return DB::table('information_schema.tables')
            ->where('table_schema', $database)
            ->where('table_name', $tableName)
            ->exists();
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    protected function existingColumns(string $tableName): array
    {
        $rows = DB::select(sprintf('SHOW FULL COLUMNS FROM %s', $this->quoteIdentifier($tableName)));
        $columns = [];

        foreach ($rows as $row) {
            $columns[$row->Field] = [
                'type' => Str::upper((string) $row->Type),
                'nullable' => $row->Null === 'YES',
                'default' => $row->Default,
            ];
        }

        return $columns;
    }

    /**
     * @return array<string, array{columns: array<int, string>, unique: bool}>
     */
    protected function existingIndexes(string $tableName): array
    {
        $rows = DB::select(sprintf('SHOW INDEX FROM %s', $this->quoteIdentifier($tableName)));
        $indexes = [];

        foreach ($rows as $row) {
            if ($row->Key_name === 'PRIMARY') {
                continue;
            }

            if (! isset($indexes[$row->Key_name])) {
                $indexes[$row->Key_name] = [
                    'columns' => [],
                    'unique' => ((int) $row->Non_unique) === 0,
                ];
            }

            $indexes[$row->Key_name]['columns'][(int) $row->Seq_in_index - 1] = $row->Column_name;
        }

        foreach ($indexes as &$index) {
            ksort($index['columns']);
            $index['columns'] = array_values($index['columns']);
        }

        return $indexes;
    }

    /**
     * @return array<string, array{columns: array<int, string>, referenced_table: string, referenced_columns: array<int, string>, on_delete: string, on_update: string}>
     */
    protected function existingForeignKeys(string $tableName): array
    {
        $database = DB::connection('mysql')->getDatabaseName();
        $rows = DB::select(
            'SELECT kcu.CONSTRAINT_NAME, kcu.COLUMN_NAME, kcu.REFERENCED_TABLE_NAME, kcu.REFERENCED_COLUMN_NAME, rc.UPDATE_RULE, rc.DELETE_RULE, kcu.ORDINAL_POSITION
             FROM information_schema.KEY_COLUMN_USAGE kcu
             JOIN information_schema.REFERENTIAL_CONSTRAINTS rc
               ON rc.CONSTRAINT_SCHEMA = kcu.CONSTRAINT_SCHEMA
              AND rc.CONSTRAINT_NAME = kcu.CONSTRAINT_NAME
             WHERE kcu.TABLE_SCHEMA = ?
               AND kcu.TABLE_NAME = ?
               AND kcu.REFERENCED_TABLE_NAME IS NOT NULL
             ORDER BY kcu.CONSTRAINT_NAME, kcu.ORDINAL_POSITION',
            [$database, $tableName]
        );

        $foreignKeys = [];

        foreach ($rows as $row) {
            if (! isset($foreignKeys[$row->CONSTRAINT_NAME])) {
                $foreignKeys[$row->CONSTRAINT_NAME] = [
                    'columns' => [],
                    'referenced_table' => $row->REFERENCED_TABLE_NAME,
                    'referenced_columns' => [],
                    'on_delete' => Str::upper((string) $row->DELETE_RULE),
                    'on_update' => Str::upper((string) $row->UPDATE_RULE),
                ];
            }

            $foreignKeys[$row->CONSTRAINT_NAME]['columns'][(int) $row->ORDINAL_POSITION - 1] = $row->COLUMN_NAME;
            $foreignKeys[$row->CONSTRAINT_NAME]['referenced_columns'][(int) $row->ORDINAL_POSITION - 1] = $row->REFERENCED_COLUMN_NAME;
        }

        foreach ($foreignKeys as &$foreignKey) {
            ksort($foreignKey['columns']);
            ksort($foreignKey['referenced_columns']);
            $foreignKey['columns'] = array_values($foreignKey['columns']);
            $foreignKey['referenced_columns'] = array_values($foreignKey['referenced_columns']);
        }

        return $foreignKeys;
    }

    /**
     * @param  array<string, mixed>  $definition
     */
    protected function buildCreateTableSql(string $tableName, array $definition): string
    {
        $parts = ['`id` INT NOT NULL AUTO_INCREMENT'];

        foreach ($this->arrayValue($definition, 'Columns', []) as $column) {
            $parts[] = $this->buildColumnDefinition($column);
        }

        $parts[] = 'PRIMARY KEY (`id`)';

        return sprintf(
            'CREATE TABLE %s (%s) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci',
            $this->quoteIdentifier($tableName),
            implode(', ', $parts)
        );
    }

    /**
     * @param  array<string, mixed>  $column
     */
    protected function buildColumnDefinition(array $column): string
    {
        $name = $this->stringValue($column, 'Name');
        $type = Str::upper($this->stringValue($column, 'Type'));
        $nullable = (bool) $this->arrayValue($column, 'Nullable', false);
        $defaultSql = $this->arrayValue($column, 'DefaultSql');

        $segments = [sprintf('%s %s', $this->quoteIdentifier($name), $type)];
        $segments[] = $nullable ? 'NULL' : 'NOT NULL';

        if ($defaultSql !== null) {
            $segments[] = sprintf('DEFAULT %s', $defaultSql);
        }

        return implode(' ', $segments);
    }

    /**
     * @param  array<string, mixed>  $column
     */
    protected function columnNeedsChange(array $existingColumn, array $column): bool
    {
        $expectedType = Str::upper($this->stringValue($column, 'Type'));
        $expectedNullable = (bool) $this->arrayValue($column, 'Nullable', false);
        $expectedDefault = $this->arrayValue($column, 'DefaultSql');
        $actualDefault = $existingColumn['default'];

        return $existingColumn['type'] !== $expectedType
            || $existingColumn['nullable'] !== $expectedNullable
            || (($expectedDefault !== null ? Str::upper((string) $expectedDefault) : null) !== ($actualDefault !== null ? Str::upper((string) $actualDefault) : null));
    }

    /**
     * @param  array<string, mixed>  $config
     */
    protected function prefixedTableName(array $config, string $tableName): string
    {
        return (string) $config['db_prefix'].$tableName;
    }

    /**
     * @param  array<string, mixed>  $config
     */
    protected function getSysValue(array $config, string $name, ?string $default = null): ?string
    {
        $sysTable = $this->prefixedTableName($config, 'sys');

        if (! $this->tableExists($sysTable)) {
            return $default;
        }

        $value = DB::table($sysTable)->where('name', $name)->value('value');

        return $value !== null ? (string) $value : $default;
    }

    /**
     * @param  array<string, mixed>  $config
     */
    protected function setSysValue(array $config, string $name, string $value): void
    {
        $sysTable = $this->prefixedTableName($config, 'sys');

        DB::table($sysTable)->updateOrInsert(['name' => $name], ['value' => $value]);
    }

    protected function quoteIdentifier(string $identifier): string
    {
        return '`'.str_replace('`', '``', $identifier).'`';
    }

    /**
     * @return array<string, mixed>
     */
    protected function loadJson(string $path): array
    {
        try {
            $contents = file_get_contents($path);
            if ($contents === false) {
                throw new RuntimeException(sprintf('Unable to read %s', $path));
            }

            $decoded = json_decode($this->stripBom($contents), true, 512, JSON_THROW_ON_ERROR);

            if (! is_array($decoded)) {
                throw new RuntimeException(sprintf('Unexpected JSON payload in %s', $path));
            }

            return $decoded;
        } catch (Throwable $exception) {
            Log::build([
                'driver' => 'single',
                'path' => storage_path('logs/dotpos-schema.log'),
            ])->error($exception->getMessage(), ['exception' => $exception]);

            throw $exception instanceof RuntimeException ? $exception : new RuntimeException($exception->getMessage(), 0, $exception);
        }
    }

    protected function stripBom(string $contents): string
    {
        return preg_replace('/^\xEF\xBB\xBF/', '', $contents) ?? $contents;
    }

    /**
     * @param  array<string, mixed>  $source
     */
    protected function stringValue(array $source, string $key, string $default = ''): string
    {
        $value = $this->arrayValue($source, $key, $default);

        return is_scalar($value) ? (string) $value : $default;
    }

    /**
     * @param  array<string, mixed>  $source
     */
    protected function arrayValue(array $source, string $key, mixed $default = null): mixed
    {
        foreach ($source as $sourceKey => $value) {
            if (strcasecmp((string) $sourceKey, $key) === 0) {
                return $value;
            }
        }

        return $default;
    }
}
