<?php

namespace Tests\Unit;

use App\Support\SchemaGeneration\DatabaseSchemaValidator;
use App\Support\SchemaGeneration\LocalSchemaManifest;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Tests\TestCase;

class DatabaseSchemaValidatorTest extends TestCase
{
    public function test_validate_fails_when_only_foreign_key_name_differs(): void
    {
        Schema::dropIfExists('orders');
        Schema::create('orders', function (Blueprint $table): void {
            $table->id();
            $table->char('location_guid', 36);
        });

        $manifest = new class extends LocalSchemaManifest
        {
            public function basePath(): string
            {
                return '/virtual/reference/LocalDatabaseSchema';
            }

            public function load(): array
            {
                return [
                    'tables' => [
                        [
                            'table_name' => 'orders',
                            'file' => 'tables/orders.table.json',
                            'definition' => [
                                'columns' => [
                                    [
                                        'Name' => 'location_guid',
                                        'Type' => 'CHAR(36)',
                                        'Nullable' => false,
                                    ],
                                ],
                                'foreign_keys' => [
                                    [
                                        'Name' => 'fk_orders_location_guid',
                                        'Columns' => ['location_guid'],
                                        'ReferencedTable' => 'locations',
                                        'ReferencedColumns' => ['guid'],
                                        'OnDelete' => 'RESTRICT',
                                        'OnUpdate' => 'CASCADE',
                                    ],
                                ],
                            ],
                        ],
                    ],
                    'seeds' => [],
                ];
            }
        };

        $validator = new class($manifest) extends DatabaseSchemaValidator
        {
            /**
             * @return array<string, array{type: string, nullable: bool}>
             */
            protected function describeTable(string $connection, string $databaseName, string $tableName): array
            {
                return [
                    'location_guid' => [
                        'type' => 'CHAR(36)',
                        'nullable' => false,
                    ],
                ];
            }

            /**
             * @return array<int, array{name: string|null, columns: array<int, string>, referenced_table: string, referenced_columns: array<int, string>, on_delete: string|null, on_update: string|null}>
             */
            protected function describeForeignKeys(string $connection, string $databaseName, string $tableName): array
            {
                return [
                    [
                        'name' => 'fk_orders_location_guid_renamed',
                        'columns' => ['location_guid'],
                        'referenced_table' => 'locations',
                        'referenced_columns' => ['guid'],
                        'on_delete' => 'RESTRICT',
                        'on_update' => 'CASCADE',
                    ],
                ];
            }
        };

        $result = $validator->validate();

        $this->assertFalse($result['valid']);
        $this->assertSame(1, $result['summary']['issue_count']);
        $this->assertSame([
            [
                'columns' => ['location_guid'],
                'expected' => 'fk_orders_location_guid',
                'actual' => 'fk_orders_location_guid_renamed',
            ],
        ], $result['tables'][0]['foreign_key_name_mismatches']);
        $this->assertSame('foreign_key_name_mismatch', $result['issues'][0]['issue']);
    }
}
