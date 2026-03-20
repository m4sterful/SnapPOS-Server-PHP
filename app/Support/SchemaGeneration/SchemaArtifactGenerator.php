<?php

namespace App\Support\SchemaGeneration;

use Illuminate\Support\Str;
use RuntimeException;

class SchemaArtifactGenerator
{
    public function __construct(private readonly LocalSchemaManifest $manifest) {}

    /**
     * @return array{migrations: array<int, string>, seeders: array<int, string>}
     */
    public function generate(): array
    {
        $schema = $this->manifest->load();

        $this->deletePreviousArtifacts();

        $migrations = [];
        foreach (array_values($schema['tables']) as $index => $table) {
            $filename = sprintf(
                '%s_create_generated_local_schema_%s_table.php',
                $this->migrationTimestamp($index + 1),
                $table['table_name']
            );

            file_put_contents(database_path('migrations/'.$filename), $this->renderCreateTableMigration($table));
            $migrations[] = 'database/migrations/'.$filename;
        }

        $foreignKeyFilename = $this->migrationTimestamp(500).'_add_generated_local_schema_foreign_keys.php';
        file_put_contents(
            database_path('migrations/'.$foreignKeyFilename),
            $this->renderForeignKeysMigration($schema['tables'])
        );
        $migrations[] = 'database/migrations/'.$foreignKeyFilename;

        file_put_contents(database_path('seeders/GeneratedLocalSchemaSeeder.php'), $this->renderSeeder($schema['seeds']));

        return [
            'migrations' => $migrations,
            'seeders' => ['database/seeders/GeneratedLocalSchemaSeeder.php'],
        ];
    }

    protected function deletePreviousArtifacts(): void
    {
        foreach (glob(database_path('migrations/*_generated_local_schema_*.php')) ?: [] as $path) {
            @unlink($path);
        }

        $seederPath = database_path('seeders/GeneratedLocalSchemaSeeder.php');
        if (is_file($seederPath)) {
            @unlink($seederPath);
        }
    }

    protected function migrationTimestamp(int $sequence): string
    {
        return sprintf('2026_01_01_%06d', $sequence);
    }

    /**
     * @param  array<string, mixed>  $table
     */
    protected function renderCreateTableMigration(array $table): string
    {
        $body = ["            Schema::create('{$table['table_name']}', function (Blueprint \$table): void {", '                $table->id();'];

        foreach ($table['definition']['columns'] as $column) {
            $body[] = '                '.$this->renderColumn($column);
        }

        foreach ($table['definition']['indexes'] as $index) {
            $body[] = sprintf(
                '                $table->index(%s, %s);',
                $this->exportArray($index['Columns'] ?? $index['columns'] ?? []),
                var_export((string) ($index['Name'] ?? $index['name']), true)
            );
        }

        foreach ($table['definition']['unique_indexes'] as $index) {
            $body[] = sprintf(
                '                $table->unique(%s, %s);',
                $this->exportArray($index['Columns'] ?? $index['columns'] ?? []),
                var_export((string) ($index['Name'] ?? $index['name']), true)
            );
        }

        $body[] = '            });';

        return $this->wrapMigration(
            $body,
            [
                'use Illuminate\\Database\\Migrations\\Migration;',
                'use Illuminate\\Database\\Schema\\Blueprint;',
                'use Illuminate\\Database\\Query\\Expression;',
                'use Illuminate\\Support\\Facades\\Schema;',
            ],
            [sprintf("            Schema::dropIfExists('%s');", $table['table_name'])]
        );
    }

    /**
     * @param  array<int, array<string, mixed>>  $tables
     */
    protected function renderForeignKeysMigration(array $tables): string
    {
        $upBody = ['            Schema::disableForeignKeyConstraints();', ''];
        $downBody = ['            Schema::disableForeignKeyConstraints();', ''];
        $hasForeignKeys = false;

        foreach ($tables as $table) {
            $foreignKeys = $table['definition']['foreign_keys'];
            if ($foreignKeys === []) {
                continue;
            }

            $hasForeignKeys = true;
            $upBody[] = "            Schema::table('{$table['table_name']}', function (Blueprint \$table): void {";
            foreach ($foreignKeys as $foreignKey) {
                $statement = sprintf(
                    "                \$table->foreign(%s, %s)->references(%s)->on('%s')",
                    $this->exportArray($foreignKey['Columns'] ?? $foreignKey['columns'] ?? []),
                    var_export((string) ($foreignKey['Name'] ?? $foreignKey['name']), true),
                    $this->exportArray($foreignKey['ReferencedColumns'] ?? $foreignKey['referencedColumns'] ?? []),
                    (string) ($foreignKey['ReferencedTable'] ?? $foreignKey['referencedTable'])
                );

                if (filled($foreignKey['OnDelete'] ?? $foreignKey['onDelete'] ?? null)) {
                    $statement .= "->onDelete('".Str::lower((string) ($foreignKey['OnDelete'] ?? $foreignKey['onDelete']))."')";
                }

                if (filled($foreignKey['OnUpdate'] ?? $foreignKey['onUpdate'] ?? null)) {
                    $statement .= "->onUpdate('".Str::lower((string) ($foreignKey['OnUpdate'] ?? $foreignKey['onUpdate']))."')";
                }

                $upBody[] = $statement.';';
            }
            $upBody[] = '            });';
            $upBody[] = '';
        }

        if (! $hasForeignKeys) {
            $upBody[] = '            // No foreign keys were declared in the JSON schema.';
        }
        $upBody[] = '            Schema::enableForeignKeyConstraints();';

        foreach (array_reverse($tables) as $table) {
            $foreignKeys = $table['definition']['foreign_keys'];
            if ($foreignKeys === []) {
                continue;
            }

            $downBody[] = "            Schema::table('{$table['table_name']}', function (Blueprint \$table): void {";
            foreach ($foreignKeys as $foreignKey) {
                $downBody[] = sprintf(
                    '                $table->dropForeign(%s);',
                    var_export((string) ($foreignKey['Name'] ?? $foreignKey['name']), true)
                );
            }
            $downBody[] = '            });';
            $downBody[] = '';
        }

        if (! $hasForeignKeys) {
            $downBody[] = '            // No foreign keys were declared in the JSON schema.';
        }
        $downBody[] = '            Schema::enableForeignKeyConstraints();';

        return $this->wrapMigration(
            $upBody,
            [
                'use Illuminate\\Database\\Migrations\\Migration;',
                'use Illuminate\\Database\\Schema\\Blueprint;',
                'use Illuminate\\Support\\Facades\\Schema;',
            ],
            $downBody,
        );
    }

    /**
     * @param  array<int, array<string, mixed>>  $seeds
     */
    protected function renderSeeder(array $seeds): string
    {
        $export = var_export($seeds, true);

        return <<<PHP
<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class GeneratedLocalSchemaSeeder extends Seeder
{
    /**
     * @var array<int, array<string, mixed>>
     */
    protected array \$seeds = {$export};

    public function run(): void
    {
        foreach (\$this->seeds as \$seed) {
            \$definition = \$seed['definition'];
            \$table = \$definition['table_name'];
            \$mode = \$definition['mode'];
            \$rows = array_map(fn (array \$row): array => \$this->resolveRow(\$row), \$definition['rows']);

            match (\$mode) {
                'ensure_missing_rows' => \$this->ensureMissingRows(\$table, \$definition, \$rows),
                'patch_existing_when_empty' => \$this->patchExistingWhenEmpty(\$table, \$definition, \$rows),
                'insert_all_if_table_empty' => \$this->insertAllIfTableEmpty(\$table, \$rows),
                default => throw new \RuntimeException('Unsupported generated seed mode: '.\$mode),
            };
        }
    }

    /**
     * @param  array<string, mixed>  \$definition
     * @param  array<int, array<string, mixed>>  \$rows
     */
    protected function ensureMissingRows(string \$table, array \$definition, array \$rows): void
    {
        foreach (\$rows as \$row) {
            \$match = Arr::only(\$row, \$definition['match_columns']);

            if (\$match === [] || ! DB::table(\$table)->where(\$match)->exists()) {
                DB::table(\$table)->insert(\$row);
            }
        }
    }

    /**
     * @param  array<string, mixed>  \$definition
     * @param  array<int, array<string, mixed>>  \$rows
     */
    protected function patchExistingWhenEmpty(string \$table, array \$definition, array \$rows): void
    {
        foreach (\$rows as \$row) {
            \$match = Arr::only(\$row, \$definition['match_columns']);
            \$existing = \$match === [] ? null : DB::table(\$table)->where(\$match)->first();

            if (! \$existing) {
                DB::table(\$table)->insert(\$row);
                continue;
            }

            \$updates = [];
            foreach (\$definition['patch_columns_when_empty'] as \$column) {
                \$currentValue = \$existing->{\$column} ?? null;
                \$isEmpty = \$currentValue === null || \$currentValue === '';

                if (in_array(\$column, \$definition['zero_is_empty_columns'], true)) {
                    \$isEmpty = \$isEmpty || \$currentValue === 0 || \$currentValue === '0';
                }

                if (\$isEmpty && array_key_exists(\$column, \$row)) {
                    \$updates[\$column] = \$row[\$column];
                }
            }

            if (\$updates !== []) {
                DB::table(\$table)->where(\$match)->update(\$updates);
            }
        }
    }

    /**
     * @param  array<int, array<string, mixed>>  \$rows
     */
    protected function insertAllIfTableEmpty(string \$table, array \$rows): void
    {
        if (DB::table(\$table)->count() === 0 && \$rows !== []) {
            DB::table(\$table)->insert(\$rows);
        }
    }

    /**
     * @param  array<string, mixed>  \$row
     * @return array<string, mixed>
     */
    protected function resolveRow(array \$row): array
    {
        \$values = \$row['Values'] ?? \$row['values'] ?? [];

        foreach ((\$row['Lookups'] ?? \$row['lookups'] ?? []) as \$lookup) {
            \$resolved = DB::table((string) (\$lookup['LookupTable'] ?? \$lookup['lookupTable']))
                ->where((string) (\$lookup['LookupColumn'] ?? \$lookup['lookupColumn']), \$lookup['LookupValue'] ?? \$lookup['lookupValue'])
                ->value((string) (\$lookup['SelectColumn'] ?? \$lookup['selectColumn']));

            if (\$resolved === null && (bool) (\$lookup['Required'] ?? \$lookup['required'] ?? false)) {
                throw new \RuntimeException('Unable to resolve required seed lookup for '.(string) (\$lookup['TargetColumn'] ?? \$lookup['targetColumn']));
            }

            \$values[(string) (\$lookup['TargetColumn'] ?? \$lookup['targetColumn'])] = \$resolved;
        }

        return \$values;
    }
}
PHP;
    }

    /**
     * @param  array<string, mixed>  $column
     */
    protected function renderColumn(array $column): string
    {
        $name = (string) ($column['Name'] ?? $column['name']);
        $type = strtoupper((string) ($column['Type'] ?? $column['type']));
        $nullable = (bool) ($column['Nullable'] ?? $column['nullable'] ?? false);
        $defaultSql = $column['DefaultSql'] ?? $column['defaultSql'] ?? null;

        $statement = match (true) {
            $type === 'INT' => "\$table->integer('{$name}')",
            $type === 'TEXT' => "\$table->text('{$name}')",
            $type === 'DATE' => "\$table->date('{$name}')",
            $type === 'DATETIME' => "\$table->dateTime('{$name}')",
            Str::startsWith($type, 'VARCHAR(') => "\$table->string('{$name}', {$this->extractLength($type)})",
            Str::startsWith($type, 'CHAR(') => "\$table->char('{$name}', {$this->extractLength($type)})",
            Str::startsWith($type, 'DECIMAL(') => sprintf("\$table->decimal('%s', %s, %s)", $name, ...$this->extractPrecision($type)),
            default => throw new RuntimeException("Unsupported column type [{$type}] for column [{$name}]."),
        };

        if ($nullable) {
            $statement .= '->nullable()';
        }

        if ($defaultSql !== null) {
            $statement .= strtoupper((string) $defaultSql) === 'CURRENT_TIMESTAMP'
                ? '->useCurrent()'
                : '->default(new Expression('.var_export((string) $defaultSql, true).'))';
        }

        return $statement.';';
    }

    protected function extractLength(string $type): int
    {
        if (preg_match('/\((\d+)\)/', $type, $matches) !== 1) {
            throw new RuntimeException("Unable to extract a string length from [{$type}].");
        }

        return (int) $matches[1];
    }

    /**
     * @return array{0: int, 1: int}
     */
    protected function extractPrecision(string $type): array
    {
        if (preg_match('/\((\d+),(\d+)\)/', $type, $matches) !== 1) {
            throw new RuntimeException("Unable to extract decimal precision from [{$type}].");
        }

        return [(int) $matches[1], (int) $matches[2]];
    }

    /**
     * @param  array<int, mixed>  $values
     */
    protected function exportArray(array $values): string
    {
        return '['.implode(', ', array_map(fn (mixed $value): string => var_export($value, true), $values)).']';
    }

    /**
     * @param  array<int, string>  $imports
     * @param  array<int, string>  $downBody
     */
    protected function wrapMigration(array $upBody, array $imports, array $downBody): string
    {
        return implode(PHP_EOL, [
            '<?php',
            '',
            ...$imports,
            '',
            'return new class extends Migration',
            '{',
            '    public function up(): void',
            '    {',
            ...$upBody,
            '    }',
            '',
            '    public function down(): void',
            '    {',
            ...$downBody,
            '    }',
            '};',
            '',
        ]);
    }
}
