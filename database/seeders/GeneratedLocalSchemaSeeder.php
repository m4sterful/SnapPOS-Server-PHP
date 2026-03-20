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
    protected array $seeds = [
        [
            'seed_name' => 'userroles_defaults',
            'version' => 2,
            'version_key_name' => 'UserRolesDefaults',
            'file' => 'seeds/userroles.seed.json',
            'definition' => [
                'table_name' => 'userroles',
                'mode' => 'ensure_missing_rows',
                'match_columns' => [
                    'name'
                ],
                'patch_columns_when_empty' => [

                ],
                'zero_is_empty_columns' => [

                ],
                'rows' => [
                    [
                        'Values' => [
                            'name' => 'Administrators'
                        ],
                        'Lookups' => [

                        ]
                    ],
                    [
                        'Values' => [
                            'name' => 'Purchasing'
                        ],
                        'Lookups' => [

                        ]
                    ],
                    [
                        'Values' => [
                            'name' => 'Sales'
                        ],
                        'Lookups' => [

                        ]
                    ],
                    [
                        'Values' => [
                            'name' => 'Area Manager'
                        ],
                        'Lookups' => [

                        ]
                    ],
                    [
                        'Values' => [
                            'name' => 'Home Delivery'
                        ],
                        'Lookups' => [

                        ]
                    ],
                    [
                        'Values' => [
                            'name' => 'Store Manager'
                        ],
                        'Lookups' => [

                        ]
                    ],
                    [
                        'Values' => [
                            'name' => 'Warehouse'
                        ],
                        'Lookups' => [

                        ]
                    ],
                    [
                        'Values' => [
                            'name' => 'Warehouse Admin'
                        ],
                        'Lookups' => [

                        ]
                    ],
                    [
                        'Values' => [
                            'name' => 'Inventory'
                        ],
                        'Lookups' => [

                        ]
                    ],
                    [
                        'Values' => [
                            'name' => 'Finance'
                        ],
                        'Lookups' => [

                        ]
                    ],
                    [
                        'Values' => [
                            'name' => 'Repairs'
                        ],
                        'Lookups' => [

                        ]
                    ],
                    [
                        'Values' => [
                            'name' => 'Marketing'
                        ],
                        'Lookups' => [

                        ]
                    ],
                    [
                        'Values' => [
                            'name' => 'Customer Service'
                        ],
                        'Lookups' => [

                        ]
                    ],
                    [
                        'Values' => [
                            'name' => 'Restricted'
                        ],
                        'Lookups' => [

                        ]
                    ],
                    [
                        'Values' => [
                            'name' => 'Public Web'
                        ],
                        'Lookups' => [

                        ]
                    ],
                    [
                        'Values' => [
                            'name' => 'No Access'
                        ],
                        'Lookups' => [

                        ]
                    ]
                ]
            ]
        ],
        [
            'seed_name' => 'rolepermissions_defaults',
            'version' => 1,
            'version_key_name' => 'RolePermissionsDefaults',
            'file' => 'seeds/rolepermissions.seed.json',
            'definition' => [
                'table_name' => 'rolepermissions',
                'mode' => 'insert_all_if_table_empty',
                'match_columns' => [

                ],
                'patch_columns_when_empty' => [

                ],
                'zero_is_empty_columns' => [

                ],
                'rows' => [
                    [
                        'Values' => [
                            'role_id' => 0,
                            'api_str' => 'GET /api/warehouse/locations'
                        ],
                        'Lookups' => [
                            [
                                'TargetColumn' => 'role_id',
                                'LookupTable' => 'userroles',
                                'LookupColumn' => 'name',
                                'SelectColumn' => 'id',
                                'LookupValue' => 'Warehouse',
                                'Required' => true
                            ]
                        ]
                    ],
                    [
                        'Values' => [
                            'role_id' => 0,
                            'api_str' => 'GET /api/warehouse/orders'
                        ],
                        'Lookups' => [
                            [
                                'TargetColumn' => 'role_id',
                                'LookupTable' => 'userroles',
                                'LookupColumn' => 'name',
                                'SelectColumn' => 'id',
                                'LookupValue' => 'Warehouse',
                                'Required' => true
                            ]
                        ]
                    ],
                    [
                        'Values' => [
                            'role_id' => 0,
                            'api_str' => 'GET /api/warehouse/orders/{id}'
                        ],
                        'Lookups' => [
                            [
                                'TargetColumn' => 'role_id',
                                'LookupTable' => 'userroles',
                                'LookupColumn' => 'name',
                                'SelectColumn' => 'id',
                                'LookupValue' => 'Warehouse',
                                'Required' => true
                            ]
                        ]
                    ],
                    [
                        'Values' => [
                            'role_id' => 0,
                            'api_str' => 'POST /api/warehouse/orders/{id}/status'
                        ],
                        'Lookups' => [
                            [
                                'TargetColumn' => 'role_id',
                                'LookupTable' => 'userroles',
                                'LookupColumn' => 'name',
                                'SelectColumn' => 'id',
                                'LookupValue' => 'Warehouse',
                                'Required' => true
                            ]
                        ]
                    ],
                    [
                        'Values' => [
                            'role_id' => 0,
                            'api_str' => 'GET /api/warehouse/locations'
                        ],
                        'Lookups' => [
                            [
                                'TargetColumn' => 'role_id',
                                'LookupTable' => 'userroles',
                                'LookupColumn' => 'name',
                                'SelectColumn' => 'id',
                                'LookupValue' => 'Warehouse Admin',
                                'Required' => true
                            ]
                        ]
                    ],
                    [
                        'Values' => [
                            'role_id' => 0,
                            'api_str' => 'GET /api/warehouse/orders'
                        ],
                        'Lookups' => [
                            [
                                'TargetColumn' => 'role_id',
                                'LookupTable' => 'userroles',
                                'LookupColumn' => 'name',
                                'SelectColumn' => 'id',
                                'LookupValue' => 'Warehouse Admin',
                                'Required' => true
                            ]
                        ]
                    ],
                    [
                        'Values' => [
                            'role_id' => 0,
                            'api_str' => 'GET /api/warehouse/orders/queued'
                        ],
                        'Lookups' => [
                            [
                                'TargetColumn' => 'role_id',
                                'LookupTable' => 'userroles',
                                'LookupColumn' => 'name',
                                'SelectColumn' => 'id',
                                'LookupValue' => 'Warehouse Admin',
                                'Required' => true
                            ]
                        ]
                    ],
                    [
                        'Values' => [
                            'role_id' => 0,
                            'api_str' => 'GET /api/warehouse/orders/{id}'
                        ],
                        'Lookups' => [
                            [
                                'TargetColumn' => 'role_id',
                                'LookupTable' => 'userroles',
                                'LookupColumn' => 'name',
                                'SelectColumn' => 'id',
                                'LookupValue' => 'Warehouse Admin',
                                'Required' => true
                            ]
                        ]
                    ],
                    [
                        'Values' => [
                            'role_id' => 0,
                            'api_str' => 'POST /api/warehouse/orders/{id}/status'
                        ],
                        'Lookups' => [
                            [
                                'TargetColumn' => 'role_id',
                                'LookupTable' => 'userroles',
                                'LookupColumn' => 'name',
                                'SelectColumn' => 'id',
                                'LookupValue' => 'Warehouse Admin',
                                'Required' => true
                            ]
                        ]
                    ],
                    [
                        'Values' => [
                            'role_id' => 0,
                            'api_str' => 'POST /api/warehouse/orders/{id}/release'
                        ],
                        'Lookups' => [
                            [
                                'TargetColumn' => 'role_id',
                                'LookupTable' => 'userroles',
                                'LookupColumn' => 'name',
                                'SelectColumn' => 'id',
                                'LookupValue' => 'Warehouse Admin',
                                'Required' => true
                            ]
                        ]
                    ],
                    [
                        'Values' => [
                            'role_id' => 0,
                            'api_str' => 'GET /api/warehouse/locations'
                        ],
                        'Lookups' => [
                            [
                                'TargetColumn' => 'role_id',
                                'LookupTable' => 'userroles',
                                'LookupColumn' => 'name',
                                'SelectColumn' => 'id',
                                'LookupValue' => 'Administrators',
                                'Required' => true
                            ]
                        ]
                    ],
                    [
                        'Values' => [
                            'role_id' => 0,
                            'api_str' => 'GET /api/warehouse/orders'
                        ],
                        'Lookups' => [
                            [
                                'TargetColumn' => 'role_id',
                                'LookupTable' => 'userroles',
                                'LookupColumn' => 'name',
                                'SelectColumn' => 'id',
                                'LookupValue' => 'Administrators',
                                'Required' => true
                            ]
                        ]
                    ],
                    [
                        'Values' => [
                            'role_id' => 0,
                            'api_str' => 'GET /api/warehouse/orders/queued'
                        ],
                        'Lookups' => [
                            [
                                'TargetColumn' => 'role_id',
                                'LookupTable' => 'userroles',
                                'LookupColumn' => 'name',
                                'SelectColumn' => 'id',
                                'LookupValue' => 'Administrators',
                                'Required' => true
                            ]
                        ]
                    ],
                    [
                        'Values' => [
                            'role_id' => 0,
                            'api_str' => 'GET /api/warehouse/orders/{id}'
                        ],
                        'Lookups' => [
                            [
                                'TargetColumn' => 'role_id',
                                'LookupTable' => 'userroles',
                                'LookupColumn' => 'name',
                                'SelectColumn' => 'id',
                                'LookupValue' => 'Administrators',
                                'Required' => true
                            ]
                        ]
                    ],
                    [
                        'Values' => [
                            'role_id' => 0,
                            'api_str' => 'POST /api/warehouse/orders/{id}/status'
                        ],
                        'Lookups' => [
                            [
                                'TargetColumn' => 'role_id',
                                'LookupTable' => 'userroles',
                                'LookupColumn' => 'name',
                                'SelectColumn' => 'id',
                                'LookupValue' => 'Administrators',
                                'Required' => true
                            ]
                        ]
                    ],
                    [
                        'Values' => [
                            'role_id' => 0,
                            'api_str' => 'POST /api/warehouse/orders/{id}/release'
                        ],
                        'Lookups' => [
                            [
                                'TargetColumn' => 'role_id',
                                'LookupTable' => 'userroles',
                                'LookupColumn' => 'name',
                                'SelectColumn' => 'id',
                                'LookupValue' => 'Administrators',
                                'Required' => true
                            ]
                        ]
                    ]
                ]
            ]
        ],
        [
            'seed_name' => 'users_admin_default',
            'version' => 1,
            'version_key_name' => 'UsersAdminDefault',
            'file' => 'seeds/users-admin.seed.json',
            'definition' => [
                'table_name' => 'users',
                'mode' => 'ensure_missing_rows',
                'match_columns' => [
                    'username'
                ],
                'patch_columns_when_empty' => [

                ],
                'zero_is_empty_columns' => [

                ],
                'rows' => [
                    [
                        'Values' => [
                            'username' => 'admin',
                            'password_hash' => '815748bdbb1078b8f9cfed63e22b828b2f85918fbaa2c9fb46e04863c8bd149b',
                            'salt' => 'dotpos-default-admin-seed-salt-01',
                            'location' => 1,
                            'created_by' => 1,
                            'active' => 1
                        ],
                        'Lookups' => [
                            [
                                'TargetColumn' => 'permissions',
                                'LookupTable' => 'userroles',
                                'LookupColumn' => 'name',
                                'SelectColumn' => 'id',
                                'LookupValue' => 'Administrators',
                                'Required' => false
                            ]
                        ]
                    ]
                ]
            ]
        ],
        [
            'seed_name' => 'users_warehouse_default',
            'version' => 1,
            'version_key_name' => 'UsersWarehouseDefault',
            'file' => 'seeds/users-warehouse.seed.json',
            'definition' => [
                'table_name' => 'users',
                'mode' => 'ensure_missing_rows',
                'match_columns' => [
                    'username'
                ],
                'patch_columns_when_empty' => [

                ],
                'zero_is_empty_columns' => [

                ],
                'rows' => [
                    [
                        'Values' => [
                            'username' => 'warehouse',
                            'password_hash' => '815748bdbb1078b8f9cfed63e22b828b2f85918fbaa2c9fb46e04863c8bd149b',
                            'salt' => 'dotpos-default-admin-seed-salt-01',
                            'location' => 1,
                            'created_by' => 1,
                            'active' => 1,
                            'pin' => 222222
                        ],
                        'Lookups' => [
                            [
                                'TargetColumn' => 'permissions',
                                'LookupTable' => 'userroles',
                                'LookupColumn' => 'name',
                                'SelectColumn' => 'id',
                                'LookupValue' => 'Warehouse',
                                'Required' => true
                            ]
                        ]
                    ],
                    [
                        'Values' => [
                            'username' => 'warehouseadmin',
                            'password_hash' => '815748bdbb1078b8f9cfed63e22b828b2f85918fbaa2c9fb46e04863c8bd149b',
                            'salt' => 'dotpos-default-admin-seed-salt-01',
                            'location' => 1,
                            'created_by' => 1,
                            'active' => 1,
                            'pin' => 333333
                        ],
                        'Lookups' => [
                            [
                                'TargetColumn' => 'permissions',
                                'LookupTable' => 'userroles',
                                'LookupColumn' => 'name',
                                'SelectColumn' => 'id',
                                'LookupValue' => 'Warehouse Admin',
                                'Required' => true
                            ]
                        ]
                    ]
                ]
            ]
        ],
        [
            'seed_name' => 'locations_default_seed',
            'version' => 1,
            'version_key_name' => 'LocationsDefault',
            'file' => 'seeds/locations.seed.json',
            'definition' => [
                'table_name' => 'locations',
                'mode' => 'ensure_missing_rows',
                'match_columns' => [
                    'guid'
                ],
                'patch_columns_when_empty' => [

                ],
                'zero_is_empty_columns' => [

                ],
                'rows' => [
                    [
                        'Values' => [
                            'guid' => '11111111-1111-1111-1111-111111111111',
                            'location_name' => 'Main Warehouse',
                            'address_line1' => '100 Warehouse Way',
                            'city' => 'Toronto',
                            'province' => 'ON',
                            'postal_code' => 'M5V1A1',
                            'country' => 'Canada',
                            'location_type' => 'Warehouse',
                            'tag' => 'WH-MAIN'
                        ],
                        'Lookups' => [

                        ]
                    ],
                    [
                        'Values' => [
                            'guid' => '22222222-2222-2222-2222-222222222222',
                            'location_name' => 'Store A',
                            'address_line1' => '10 Queen Street',
                            'city' => 'Toronto',
                            'province' => 'ON',
                            'postal_code' => 'M5V1B1',
                            'country' => 'Canada',
                            'location_type' => 'Store',
                            'tag' => 'STORE-A'
                        ],
                        'Lookups' => [

                        ]
                    ],
                    [
                        'Values' => [
                            'guid' => '33333333-3333-3333-3333-333333333333',
                            'location_name' => 'Store B',
                            'address_line1' => '25 King Street',
                            'city' => 'Toronto',
                            'province' => 'ON',
                            'postal_code' => 'M5V1C1',
                            'country' => 'Canada',
                            'location_type' => 'Store',
                            'tag' => 'STORE-B'
                        ],
                        'Lookups' => [

                        ]
                    ],
                    [
                        'Values' => [
                            'guid' => '44444444-4444-4444-4444-444444444444',
                            'location_name' => 'Northline Delivery',
                            'address_line1' => '80 Harbour Street',
                            'city' => 'Toronto',
                            'province' => 'ON',
                            'postal_code' => 'M5J2L9',
                            'country' => 'Canada',
                            'location_type' => 'Customer',
                            'tag' => 'CUSTOMER-NORTHLINE'
                        ],
                        'Lookups' => [

                        ]
                    ]
                ]
            ]
        ],
        [
            'seed_name' => 'tax_defaults',
            'version' => 1,
            'version_key_name' => 'TaxDefaults',
            'file' => 'seeds/tax.seed.json',
            'definition' => [
                'table_name' => 'tax',
                'mode' => 'ensure_missing_rows',
                'match_columns' => [
                    'tax_type'
                ],
                'patch_columns_when_empty' => [

                ],
                'zero_is_empty_columns' => [

                ],
                'rows' => [
                    [
                        'Values' => [
                            'tax_type' => 'Goods & Services',
                            'description' => 'HST',
                            'rate' => 0.13
                        ],
                        'Lookups' => [

                        ]
                    ]
                ]
            ]
        ],
        [
            'seed_name' => 'pricetypes_defaults',
            'version' => 1,
            'version_key_name' => 'PriceTypesDefaults',
            'file' => 'seeds/pricetypes.seed.json',
            'definition' => [
                'table_name' => 'pricetypes',
                'mode' => 'ensure_missing_rows',
                'match_columns' => [
                    'name'
                ],
                'patch_columns_when_empty' => [

                ],
                'zero_is_empty_columns' => [

                ],
                'rows' => [
                    [
                        'Values' => [
                            'name' => 'Purchase Price',
                            'description' => 'The price paid to acquire inventory (cost price)'
                        ],
                        'Lookups' => [

                        ]
                    ],
                    [
                        'Values' => [
                            'name' => 'Landed Cost',
                            'description' => 'Total cost including shipping, duties, and taxes'
                        ],
                        'Lookups' => [

                        ]
                    ],
                    [
                        'Values' => [
                            'name' => 'Wholesale Price',
                            'description' => 'The price offered to bulk buyers or resellers'
                        ],
                        'Lookups' => [

                        ]
                    ],
                    [
                        'Values' => [
                            'name' => 'Retail Price',
                            'description' => 'The standard selling price for customers'
                        ],
                        'Lookups' => [

                        ]
                    ],
                    [
                        'Values' => [
                            'name' => 'Sale Price',
                            'description' => 'Discounted price during a sale or promotion'
                        ],
                        'Lookups' => [

                        ]
                    ],
                    [
                        'Values' => [
                            'name' => 'Clearance Price',
                            'description' => 'Discounted price for clearing old stock'
                        ],
                        'Lookups' => [

                        ]
                    ],
                    [
                        'Values' => [
                            'name' => 'MAP Price',
                            'description' => 'Minimum Advertised Price (vendor-mandated)'
                        ],
                        'Lookups' => [

                        ]
                    ],
                    [
                        'Values' => [
                            'name' => 'MSRP',
                            'description' => 'Manufacturer Suggested Retail Price'
                        ],
                        'Lookups' => [

                        ]
                    ],
                    [
                        'Values' => [
                            'name' => 'Contract Price',
                            'description' => 'Special pricing for negotiated deals (B2B customers)'
                        ],
                        'Lookups' => [

                        ]
                    ],
                    [
                        'Values' => [
                            'name' => 'Subscription Price',
                            'description' => 'Price for products sold on a subscription basis'
                        ],
                        'Lookups' => [

                        ]
                    ],
                    [
                        'Values' => [
                            'name' => 'Custom Price',
                            'description' => 'Price overrides used for special transactions'
                        ],
                        'Lookups' => [

                        ]
                    ],
                    [
                        'Values' => [
                            'name' => 'Cost+ Pricing',
                            'description' => 'Cost-based pricing with a dynamic markup'
                        ],
                        'Lookups' => [

                        ]
                    ],
                    [
                        'Values' => [
                            'name' => 'Deleted',
                            'description' => 'The underlying item has been removed. The associated Part will be in the description.'
                        ],
                        'Lookups' => [

                        ]
                    ]
                ]
            ]
        ],
        [
            'seed_name' => 'users_admin_pin_patch',
            'version' => 1,
            'version_key_name' => 'UsersAdminPin',
            'file' => 'seeds/users-admin-pin.seed.json',
            'definition' => [
                'table_name' => 'users',
                'mode' => 'patch_existing_when_empty',
                'match_columns' => [
                    'username'
                ],
                'patch_columns_when_empty' => [
                    'pin'
                ],
                'zero_is_empty_columns' => [
                    'pin'
                ],
                'rows' => [
                    [
                        'Values' => [
                            'username' => 'admin',
                            'pin' => 123456
                        ],
                        'Lookups' => [

                        ]
                    ]
                ]
            ]
        ],
        [
            'seed_name' => 'inventory_template_default_seed',
            'version' => 1,
            'version_key_name' => 'InventoryTemplateDefault',
            'file' => 'seeds/inventory-template.seed.json',
            'definition' => [
                'table_name' => 'inventory_template',
                'mode' => 'ensure_missing_rows',
                'match_columns' => [
                    'guid'
                ],
                'patch_columns_when_empty' => [

                ],
                'zero_is_empty_columns' => [

                ],
                'rows' => [
                    [
                        'Values' => [
                            'guid' => 'aaaaaaaa-aaaa-aaaa-aaaa-aaaaaaaaaaa1',
                            'product_name' => 'Metro Sofa',
                            'description' => 'Testing sofa for transfer flows.',
                            'ref_part_no' => 'SOFA-001',
                            'product_family' => 'Living Room',
                            'product_type' => 'Sofa',
                            'barcode' => '100000000001',
                            'active' => 1,
                            'allow_sales' => 1,
                            'allow_por' => 1,
                            'allow_oversale' => 0,
                            'allow_delivery' => 1,
                            'allow_finance' => 1,
                            'serialize' => 1,
                            'assembly_required' => 0,
                            'discontinued' => 0
                        ],
                        'Lookups' => [

                        ]
                    ],
                    [
                        'Values' => [
                            'guid' => 'aaaaaaaa-aaaa-aaaa-aaaa-aaaaaaaaaaa2',
                            'product_name' => 'Harbour Dining Table',
                            'description' => 'Testing dining table for warehouse shipments.',
                            'ref_part_no' => 'TABLE-002',
                            'product_family' => 'Dining',
                            'product_type' => 'Table',
                            'barcode' => '100000000002',
                            'active' => 1,
                            'allow_sales' => 1,
                            'allow_por' => 1,
                            'allow_oversale' => 0,
                            'allow_delivery' => 1,
                            'allow_finance' => 1,
                            'serialize' => 1,
                            'assembly_required' => 1,
                            'discontinued' => 0
                        ],
                        'Lookups' => [

                        ]
                    ],
                    [
                        'Values' => [
                            'guid' => 'aaaaaaaa-aaaa-aaaa-aaaa-aaaaaaaaaaa3',
                            'product_name' => 'Summit Accent Chair',
                            'description' => 'Testing chair for customer delivery flows.',
                            'ref_part_no' => 'CHAIR-003',
                            'product_family' => 'Living Room',
                            'product_type' => 'Chair',
                            'barcode' => '100000000003',
                            'active' => 1,
                            'allow_sales' => 1,
                            'allow_por' => 1,
                            'allow_oversale' => 0,
                            'allow_delivery' => 1,
                            'allow_finance' => 1,
                            'serialize' => 1,
                            'assembly_required' => 0,
                            'discontinued' => 0
                        ],
                        'Lookups' => [

                        ]
                    ]
                ]
            ]
        ],
        [
            'seed_name' => 'inventory_instance_default_seed',
            'version' => 1,
            'version_key_name' => 'InventoryInstanceDefault',
            'file' => 'seeds/inventory-instance.seed.json',
            'definition' => [
                'table_name' => 'inventory_instance',
                'mode' => 'ensure_missing_rows',
                'match_columns' => [
                    'guid'
                ],
                'patch_columns_when_empty' => [

                ],
                'zero_is_empty_columns' => [

                ],
                'rows' => [
                    [
                        'Values' => [
                            'guid' => 'bbbbbbbb-bbbb-bbbb-bbbb-bbbbbbbbb001',
                            'stock_type' => 'WarehouseStock',
                            'notes' => 'Queued warehouse-to-store test item for Store B.',
                            'location_guid' => '11111111-1111-1111-1111-111111111111',
                            'serial_number' => 'SOFA-001-UNIT-01',
                            'inventory_guid' => 'aaaaaaaa-aaaa-aaaa-aaaa-aaaaaaaaaaa1'
                        ],
                        'Lookups' => [

                        ]
                    ],
                    [
                        'Values' => [
                            'guid' => 'bbbbbbbb-bbbb-bbbb-bbbb-bbbbbbbbb002',
                            'stock_type' => 'WarehouseStock',
                            'notes' => 'Ready-to-pick customer delivery item at the warehouse.',
                            'location_guid' => '11111111-1111-1111-1111-111111111111',
                            'serial_number' => 'CHAIR-003-UNIT-01',
                            'inventory_guid' => 'aaaaaaaa-aaaa-aaaa-aaaa-aaaaaaaaaaa3'
                        ],
                        'Lookups' => [

                        ]
                    ],
                    [
                        'Values' => [
                            'guid' => 'bbbbbbbb-bbbb-bbbb-bbbb-bbbbbbbbb003',
                            'stock_type' => 'TransferStock',
                            'notes' => 'Store transfer item currently sitting in the warehouse cross-dock.',
                            'location_guid' => '11111111-1111-1111-1111-111111111111',
                            'shipment_guid' => 'cccccccc-cccc-cccc-cccc-ccccccccc003',
                            'serial_number' => 'TABLE-002-UNIT-01',
                            'inventory_guid' => 'aaaaaaaa-aaaa-aaaa-aaaa-aaaaaaaaaaa2'
                        ],
                        'Lookups' => [

                        ]
                    ],
                    [
                        'Values' => [
                            'guid' => 'bbbbbbbb-bbbb-bbbb-bbbb-bbbbbbbbb004',
                            'stock_type' => 'WarehouseStock',
                            'notes' => 'Warehouse replenishment staged for Store A.',
                            'location_guid' => '11111111-1111-1111-1111-111111111111',
                            'serial_number' => 'SOFA-001-UNIT-02',
                            'inventory_guid' => 'aaaaaaaa-aaaa-aaaa-aaaa-aaaaaaaaaaa1'
                        ],
                        'Lookups' => [

                        ]
                    ],
                    [
                        'Values' => [
                            'guid' => 'bbbbbbbb-bbbb-bbbb-bbbb-bbbbbbbbb005',
                            'stock_type' => 'StoreStock',
                            'notes' => 'Queued Store A transfer item still on the source floor.',
                            'location_guid' => '22222222-2222-2222-2222-222222222222',
                            'serial_number' => 'TABLE-002-UNIT-02',
                            'inventory_guid' => 'aaaaaaaa-aaaa-aaaa-aaaa-aaaaaaaaaaa2'
                        ],
                        'Lookups' => [

                        ]
                    ],
                    [
                        'Values' => [
                            'guid' => 'bbbbbbbb-bbbb-bbbb-bbbb-bbbbbbbbb006',
                            'stock_type' => 'StoreStock',
                            'notes' => 'Store A transfer staged and ready to ship to Store B.',
                            'location_guid' => '22222222-2222-2222-2222-222222222222',
                            'serial_number' => 'CHAIR-003-UNIT-02',
                            'inventory_guid' => 'aaaaaaaa-aaaa-aaaa-aaaa-aaaaaaaaaaa3'
                        ],
                        'Lookups' => [

                        ]
                    ],
                    [
                        'Values' => [
                            'guid' => 'bbbbbbbb-bbbb-bbbb-bbbb-bbbbbbbbb007',
                            'stock_type' => 'StoreStock',
                            'notes' => 'Store A replenishment already received from the warehouse.',
                            'location_guid' => '22222222-2222-2222-2222-222222222222',
                            'serial_number' => 'SOFA-001-UNIT-03',
                            'inventory_guid' => 'aaaaaaaa-aaaa-aaaa-aaaa-aaaaaaaaaaa1'
                        ],
                        'Lookups' => [

                        ]
                    ],
                    [
                        'Values' => [
                            'guid' => 'bbbbbbbb-bbbb-bbbb-bbbb-bbbbbbbbb008',
                            'stock_type' => 'StoreStock',
                            'notes' => 'Store B inbound warehouse shipment awaiting receipt.',
                            'location_guid' => '33333333-3333-3333-3333-333333333333',
                            'shipment_guid' => 'cccccccc-cccc-cccc-cccc-ccccccccc008',
                            'serial_number' => 'SOFA-001-UNIT-04',
                            'inventory_guid' => 'aaaaaaaa-aaaa-aaaa-aaaa-aaaaaaaaaaa1'
                        ],
                        'Lookups' => [

                        ]
                    ],
                    [
                        'Values' => [
                            'guid' => 'bbbbbbbb-bbbb-bbbb-bbbb-bbbbbbbbb009',
                            'stock_type' => 'TransferStock',
                            'notes' => 'Store B transfer is on site and waiting for confirmation.',
                            'location_guid' => '33333333-3333-3333-3333-333333333333',
                            'shipment_guid' => 'cccccccc-cccc-cccc-cccc-ccccccccc009',
                            'serial_number' => 'TABLE-002-UNIT-03',
                            'inventory_guid' => 'aaaaaaaa-aaaa-aaaa-aaaa-aaaaaaaaaaa2'
                        ],
                        'Lookups' => [

                        ]
                    ],
                    [
                        'Values' => [
                            'guid' => 'bbbbbbbb-bbbb-bbbb-bbbb-bbbbbbbbb010',
                            'stock_type' => 'StoreStock',
                            'notes' => 'Completed Store B transfer already shelved.',
                            'location_guid' => '33333333-3333-3333-3333-333333333333',
                            'serial_number' => 'TABLE-002-UNIT-04',
                            'inventory_guid' => 'aaaaaaaa-aaaa-aaaa-aaaa-aaaaaaaaaaa2'
                        ],
                        'Lookups' => [

                        ]
                    ],
                    [
                        'Values' => [
                            'guid' => 'bbbbbbbb-bbbb-bbbb-bbbb-bbbbbbbbb011',
                            'stock_type' => 'DeliveredStock',
                            'notes' => 'Northline customer delivery ready for signature.',
                            'location_guid' => '44444444-4444-4444-4444-444444444444',
                            'shipment_guid' => 'cccccccc-cccc-cccc-cccc-ccccccccc011',
                            'serial_number' => 'CHAIR-003-UNIT-03',
                            'inventory_guid' => 'aaaaaaaa-aaaa-aaaa-aaaa-aaaaaaaaaaa3'
                        ],
                        'Lookups' => [

                        ]
                    ],
                    [
                        'Values' => [
                            'guid' => 'bbbbbbbb-bbbb-bbbb-bbbb-bbbbbbbbb012',
                            'stock_type' => 'DeliveredStock',
                            'notes' => 'Northline customer delivery already completed.',
                            'location_guid' => '44444444-4444-4444-4444-444444444444',
                            'serial_number' => 'CHAIR-003-UNIT-04',
                            'inventory_guid' => 'aaaaaaaa-aaaa-aaaa-aaaa-aaaaaaaaaaa3'
                        ],
                        'Lookups' => [

                        ]
                    ],
                    [
                        'Values' => [
                            'guid' => 'bbbbbbbb-bbbb-bbbb-bbbb-bbbbbbbbb013',
                            'stock_type' => 'DeliveredStock',
                            'notes' => 'Large Northline delivery already received by the customer.',
                            'location_guid' => '44444444-4444-4444-4444-444444444444',
                            'serial_number' => 'SOFA-001-UNIT-05',
                            'inventory_guid' => 'aaaaaaaa-aaaa-aaaa-aaaa-aaaaaaaaaaa1'
                        ],
                        'Lookups' => [

                        ]
                    ]
                ]
            ]
        ],
        [
            'seed_name' => 'truck_schedule_default_seed',
            'version' => 1,
            'version_key_name' => 'TruckScheduleDefault',
            'file' => 'seeds/truck-schedule.seed.json',
            'definition' => [
                'table_name' => 'truck_schedule_entries',
                'mode' => 'insert_all_if_table_empty',
                'match_columns' => [

                ],
                'patch_columns_when_empty' => [

                ],
                'zero_is_empty_columns' => [

                ],
                'rows' => [
                    [
                        'Values' => [
                            'departure_utc' => '2026-03-24 14:00:00',
                            'time_zone_id' => 'America/Toronto',
                            'active' => 1,
                            'notes' => 'Main Warehouse route to Store B'
                        ],
                        'Lookups' => [
                            [
                                'TargetColumn' => 'warehouse_location_id',
                                'LookupTable' => 'locations',
                                'LookupColumn' => 'guid',
                                'SelectColumn' => 'id',
                                'LookupValue' => '11111111-1111-1111-1111-111111111111'
                            ],
                            [
                                'TargetColumn' => 'destination_location_id',
                                'LookupTable' => 'locations',
                                'LookupColumn' => 'guid',
                                'SelectColumn' => 'id',
                                'LookupValue' => '33333333-3333-3333-3333-333333333333'
                            ]
                        ]
                    ],
                    [
                        'Values' => [
                            'departure_utc' => '2026-03-25 13:00:00',
                            'time_zone_id' => 'America/Toronto',
                            'active' => 1,
                            'notes' => 'Store A transfer run to Store B'
                        ],
                        'Lookups' => [
                            [
                                'TargetColumn' => 'warehouse_location_id',
                                'LookupTable' => 'locations',
                                'LookupColumn' => 'guid',
                                'SelectColumn' => 'id',
                                'LookupValue' => '22222222-2222-2222-2222-222222222222'
                            ],
                            [
                                'TargetColumn' => 'destination_location_id',
                                'LookupTable' => 'locations',
                                'LookupColumn' => 'guid',
                                'SelectColumn' => 'id',
                                'LookupValue' => '33333333-3333-3333-3333-333333333333'
                            ]
                        ]
                    ]
                ]
            ]
        ],
        [
            'seed_name' => 'orders_default_seed',
            'version' => 2,
            'version_key_name' => 'OrdersDefault',
            'file' => 'seeds/orders.seed.json',
            'definition' => [
                'table_name' => 'orders',
                'mode' => 'insert_all_if_table_empty',
                'match_columns' => [

                ],
                'patch_columns_when_empty' => [

                ],
                'zero_is_empty_columns' => [

                ],
                'rows' => [
                    [
                        'Values' => [
                            'guid' => 'cccccccc-cccc-cccc-cccc-ccccccccc001',
                            'order_number' => 'WH-3001',
                            'order_type' => 'warehouse_to_store',
                            'location_label' => 'Main Warehouse',
                            'customer_name' => '',
                            'source_location_label' => 'Main Warehouse',
                            'destination_location_label' => 'Store B',
                            'vendor_id' => null,
                            'status' => 'queued',
                            'priority' => 1,
                            'item_count' => 1,
                            'order_total' => 1899.95,
                            'sales_channel' => 'Warehouse Replenishment',
                            'requested_ship_date' => '2026-03-24',
                            'notes' => 'Queued warehouse replenishment waiting for the next Store B truck.'
                        ],
                        'Lookups' => [
                            [
                                'TargetColumn' => 'location_id',
                                'LookupTable' => 'locations',
                                'LookupColumn' => 'guid',
                                'SelectColumn' => 'id',
                                'LookupValue' => '11111111-1111-1111-1111-111111111111'
                            ],
                            [
                                'TargetColumn' => 'source_location_id',
                                'LookupTable' => 'locations',
                                'LookupColumn' => 'guid',
                                'SelectColumn' => 'id',
                                'LookupValue' => '11111111-1111-1111-1111-111111111111'
                            ],
                            [
                                'TargetColumn' => 'destination_location_id',
                                'LookupTable' => 'locations',
                                'LookupColumn' => 'guid',
                                'SelectColumn' => 'id',
                                'LookupValue' => '33333333-3333-3333-3333-333333333333'
                            ]
                        ]
                    ],
                    [
                        'Values' => [
                            'guid' => 'cccccccc-cccc-cccc-cccc-ccccccccc002',
                            'order_number' => 'WH-3002',
                            'order_type' => 'warehouse_to_customer',
                            'location_label' => 'Main Warehouse',
                            'customer_name' => 'Northline Hospitality',
                            'source_location_label' => 'Main Warehouse',
                            'destination_location_label' => 'Northline Delivery',
                            'vendor_id' => null,
                            'status' => 'ready_to_pick',
                            'priority' => 2,
                            'item_count' => 1,
                            'order_total' => 499.0,
                            'sales_channel' => 'Customer Delivery',
                            'requested_ship_date' => '2026-03-22',
                            'notes' => 'Released customer order queued for the next warehouse picker.'
                        ],
                        'Lookups' => [
                            [
                                'TargetColumn' => 'location_id',
                                'LookupTable' => 'locations',
                                'LookupColumn' => 'guid',
                                'SelectColumn' => 'id',
                                'LookupValue' => '11111111-1111-1111-1111-111111111111'
                            ],
                            [
                                'TargetColumn' => 'source_location_id',
                                'LookupTable' => 'locations',
                                'LookupColumn' => 'guid',
                                'SelectColumn' => 'id',
                                'LookupValue' => '11111111-1111-1111-1111-111111111111'
                            ],
                            [
                                'TargetColumn' => 'destination_location_id',
                                'LookupTable' => 'locations',
                                'LookupColumn' => 'guid',
                                'SelectColumn' => 'id',
                                'LookupValue' => '44444444-4444-4444-4444-444444444444'
                            ]
                        ]
                    ],
                    [
                        'Values' => [
                            'guid' => 'cccccccc-cccc-cccc-cccc-ccccccccc003',
                            'order_number' => 'TR-4001',
                            'order_type' => 'store_transfer',
                            'location_label' => 'Main Warehouse',
                            'customer_name' => '',
                            'source_location_label' => 'Store A',
                            'destination_location_label' => 'Store B',
                            'vendor_id' => null,
                            'status' => 'via_wh',
                            'priority' => 2,
                            'item_count' => 1,
                            'order_total' => 1499.0,
                            'sales_channel' => 'Store Transfer',
                            'requested_ship_date' => '2026-03-21',
                            'notes' => 'Store transfer has arrived at the warehouse and is waiting for its outbound leg.'
                        ],
                        'Lookups' => [
                            [
                                'TargetColumn' => 'location_id',
                                'LookupTable' => 'locations',
                                'LookupColumn' => 'guid',
                                'SelectColumn' => 'id',
                                'LookupValue' => '11111111-1111-1111-1111-111111111111'
                            ],
                            [
                                'TargetColumn' => 'source_location_id',
                                'LookupTable' => 'locations',
                                'LookupColumn' => 'guid',
                                'SelectColumn' => 'id',
                                'LookupValue' => '22222222-2222-2222-2222-222222222222'
                            ],
                            [
                                'TargetColumn' => 'destination_location_id',
                                'LookupTable' => 'locations',
                                'LookupColumn' => 'guid',
                                'SelectColumn' => 'id',
                                'LookupValue' => '33333333-3333-3333-3333-333333333333'
                            ]
                        ]
                    ],
                    [
                        'Values' => [
                            'guid' => 'cccccccc-cccc-cccc-cccc-ccccccccc004',
                            'order_number' => 'WH-3003',
                            'order_type' => 'warehouse_to_store',
                            'location_label' => 'Main Warehouse',
                            'customer_name' => '',
                            'source_location_label' => 'Main Warehouse',
                            'destination_location_label' => 'Store A',
                            'vendor_id' => null,
                            'status' => 'ready_to_ship',
                            'priority' => 1,
                            'item_count' => 1,
                            'order_total' => 1899.95,
                            'sales_channel' => 'Warehouse Replenishment',
                            'requested_ship_date' => '2026-03-20',
                            'notes' => 'Picked warehouse replenishment staged for Store A.'
                        ],
                        'Lookups' => [
                            [
                                'TargetColumn' => 'location_id',
                                'LookupTable' => 'locations',
                                'LookupColumn' => 'guid',
                                'SelectColumn' => 'id',
                                'LookupValue' => '11111111-1111-1111-1111-111111111111'
                            ],
                            [
                                'TargetColumn' => 'source_location_id',
                                'LookupTable' => 'locations',
                                'LookupColumn' => 'guid',
                                'SelectColumn' => 'id',
                                'LookupValue' => '11111111-1111-1111-1111-111111111111'
                            ],
                            [
                                'TargetColumn' => 'destination_location_id',
                                'LookupTable' => 'locations',
                                'LookupColumn' => 'guid',
                                'SelectColumn' => 'id',
                                'LookupValue' => '22222222-2222-2222-2222-222222222222'
                            ]
                        ]
                    ],
                    [
                        'Values' => [
                            'guid' => 'cccccccc-cccc-cccc-cccc-ccccccccc005',
                            'order_number' => 'TR-4101',
                            'order_type' => 'store_transfer',
                            'location_label' => 'Store A',
                            'customer_name' => '',
                            'source_location_label' => 'Store A',
                            'destination_location_label' => 'Store B',
                            'vendor_id' => null,
                            'status' => 'queued',
                            'priority' => 2,
                            'item_count' => 1,
                            'order_total' => 1499.0,
                            'sales_channel' => 'Store Transfer',
                            'requested_ship_date' => '2026-03-25',
                            'notes' => 'Store A transfer queued until its next outbound run to Store B.'
                        ],
                        'Lookups' => [
                            [
                                'TargetColumn' => 'location_id',
                                'LookupTable' => 'locations',
                                'LookupColumn' => 'guid',
                                'SelectColumn' => 'id',
                                'LookupValue' => '22222222-2222-2222-2222-222222222222'
                            ],
                            [
                                'TargetColumn' => 'source_location_id',
                                'LookupTable' => 'locations',
                                'LookupColumn' => 'guid',
                                'SelectColumn' => 'id',
                                'LookupValue' => '22222222-2222-2222-2222-222222222222'
                            ],
                            [
                                'TargetColumn' => 'destination_location_id',
                                'LookupTable' => 'locations',
                                'LookupColumn' => 'guid',
                                'SelectColumn' => 'id',
                                'LookupValue' => '33333333-3333-3333-3333-333333333333'
                            ]
                        ]
                    ],
                    [
                        'Values' => [
                            'guid' => 'cccccccc-cccc-cccc-cccc-ccccccccc006',
                            'order_number' => 'TR-4102',
                            'order_type' => 'store_transfer',
                            'location_label' => 'Store A',
                            'customer_name' => '',
                            'source_location_label' => 'Store A',
                            'destination_location_label' => 'Store B',
                            'vendor_id' => null,
                            'status' => 'ready_to_ship',
                            'priority' => 3,
                            'item_count' => 1,
                            'order_total' => 499.0,
                            'sales_channel' => 'Store Transfer',
                            'requested_ship_date' => '2026-03-20',
                            'notes' => 'Store A has picked this transfer and staged it for Store B.'
                        ],
                        'Lookups' => [
                            [
                                'TargetColumn' => 'location_id',
                                'LookupTable' => 'locations',
                                'LookupColumn' => 'guid',
                                'SelectColumn' => 'id',
                                'LookupValue' => '22222222-2222-2222-2222-222222222222'
                            ],
                            [
                                'TargetColumn' => 'source_location_id',
                                'LookupTable' => 'locations',
                                'LookupColumn' => 'guid',
                                'SelectColumn' => 'id',
                                'LookupValue' => '22222222-2222-2222-2222-222222222222'
                            ],
                            [
                                'TargetColumn' => 'destination_location_id',
                                'LookupTable' => 'locations',
                                'LookupColumn' => 'guid',
                                'SelectColumn' => 'id',
                                'LookupValue' => '33333333-3333-3333-3333-333333333333'
                            ]
                        ]
                    ],
                    [
                        'Values' => [
                            'guid' => 'cccccccc-cccc-cccc-cccc-ccccccccc007',
                            'order_number' => 'WH-3201',
                            'order_type' => 'warehouse_to_store',
                            'location_label' => 'Store A',
                            'customer_name' => '',
                            'source_location_label' => 'Main Warehouse',
                            'destination_location_label' => 'Store A',
                            'vendor_id' => null,
                            'status' => 'received',
                            'priority' => 1,
                            'item_count' => 1,
                            'order_total' => 1899.95,
                            'sales_channel' => 'Warehouse Replenishment',
                            'requested_ship_date' => '2026-03-18',
                            'notes' => 'Store A already received this warehouse replenishment.'
                        ],
                        'Lookups' => [
                            [
                                'TargetColumn' => 'location_id',
                                'LookupTable' => 'locations',
                                'LookupColumn' => 'guid',
                                'SelectColumn' => 'id',
                                'LookupValue' => '22222222-2222-2222-2222-222222222222'
                            ],
                            [
                                'TargetColumn' => 'source_location_id',
                                'LookupTable' => 'locations',
                                'LookupColumn' => 'guid',
                                'SelectColumn' => 'id',
                                'LookupValue' => '11111111-1111-1111-1111-111111111111'
                            ],
                            [
                                'TargetColumn' => 'destination_location_id',
                                'LookupTable' => 'locations',
                                'LookupColumn' => 'guid',
                                'SelectColumn' => 'id',
                                'LookupValue' => '22222222-2222-2222-2222-222222222222'
                            ]
                        ]
                    ],
                    [
                        'Values' => [
                            'guid' => 'cccccccc-cccc-cccc-cccc-ccccccccc008',
                            'order_number' => 'WH-3301',
                            'order_type' => 'warehouse_to_store',
                            'location_label' => 'Store B',
                            'customer_name' => '',
                            'source_location_label' => 'Main Warehouse',
                            'destination_location_label' => 'Store B',
                            'vendor_id' => null,
                            'status' => 'ready_to_receive',
                            'priority' => 1,
                            'item_count' => 1,
                            'order_total' => 1899.95,
                            'sales_channel' => 'Warehouse Replenishment',
                            'requested_ship_date' => '2026-03-20',
                            'notes' => 'Store B has an inbound warehouse order ready for final receipt.'
                        ],
                        'Lookups' => [
                            [
                                'TargetColumn' => 'location_id',
                                'LookupTable' => 'locations',
                                'LookupColumn' => 'guid',
                                'SelectColumn' => 'id',
                                'LookupValue' => '33333333-3333-3333-3333-333333333333'
                            ],
                            [
                                'TargetColumn' => 'source_location_id',
                                'LookupTable' => 'locations',
                                'LookupColumn' => 'guid',
                                'SelectColumn' => 'id',
                                'LookupValue' => '11111111-1111-1111-1111-111111111111'
                            ],
                            [
                                'TargetColumn' => 'destination_location_id',
                                'LookupTable' => 'locations',
                                'LookupColumn' => 'guid',
                                'SelectColumn' => 'id',
                                'LookupValue' => '33333333-3333-3333-3333-333333333333'
                            ]
                        ]
                    ],
                    [
                        'Values' => [
                            'guid' => 'cccccccc-cccc-cccc-cccc-ccccccccc009',
                            'order_number' => 'TR-4201',
                            'order_type' => 'store_transfer',
                            'location_label' => 'Store B',
                            'customer_name' => '',
                            'source_location_label' => 'Store A',
                            'destination_location_label' => 'Store B',
                            'vendor_id' => null,
                            'status' => 'ready_to_receive',
                            'priority' => 2,
                            'item_count' => 1,
                            'order_total' => 1499.0,
                            'sales_channel' => 'Store Transfer',
                            'requested_ship_date' => '2026-03-20',
                            'notes' => 'Store B transfer is on site and ready for receipt.'
                        ],
                        'Lookups' => [
                            [
                                'TargetColumn' => 'location_id',
                                'LookupTable' => 'locations',
                                'LookupColumn' => 'guid',
                                'SelectColumn' => 'id',
                                'LookupValue' => '33333333-3333-3333-3333-333333333333'
                            ],
                            [
                                'TargetColumn' => 'source_location_id',
                                'LookupTable' => 'locations',
                                'LookupColumn' => 'guid',
                                'SelectColumn' => 'id',
                                'LookupValue' => '22222222-2222-2222-2222-222222222222'
                            ],
                            [
                                'TargetColumn' => 'destination_location_id',
                                'LookupTable' => 'locations',
                                'LookupColumn' => 'guid',
                                'SelectColumn' => 'id',
                                'LookupValue' => '33333333-3333-3333-3333-333333333333'
                            ]
                        ]
                    ],
                    [
                        'Values' => [
                            'guid' => 'cccccccc-cccc-cccc-cccc-ccccccccc010',
                            'order_number' => 'TR-4202',
                            'order_type' => 'store_transfer',
                            'location_label' => 'Store B',
                            'customer_name' => '',
                            'source_location_label' => 'Store A',
                            'destination_location_label' => 'Store B',
                            'vendor_id' => null,
                            'status' => 'received',
                            'priority' => 2,
                            'item_count' => 1,
                            'order_total' => 1499.0,
                            'sales_channel' => 'Store Transfer',
                            'requested_ship_date' => '2026-03-18',
                            'notes' => 'Completed store-to-store transfer from Store A.'
                        ],
                        'Lookups' => [
                            [
                                'TargetColumn' => 'location_id',
                                'LookupTable' => 'locations',
                                'LookupColumn' => 'guid',
                                'SelectColumn' => 'id',
                                'LookupValue' => '33333333-3333-3333-3333-333333333333'
                            ],
                            [
                                'TargetColumn' => 'source_location_id',
                                'LookupTable' => 'locations',
                                'LookupColumn' => 'guid',
                                'SelectColumn' => 'id',
                                'LookupValue' => '22222222-2222-2222-2222-222222222222'
                            ],
                            [
                                'TargetColumn' => 'destination_location_id',
                                'LookupTable' => 'locations',
                                'LookupColumn' => 'guid',
                                'SelectColumn' => 'id',
                                'LookupValue' => '33333333-3333-3333-3333-333333333333'
                            ]
                        ]
                    ],
                    [
                        'Values' => [
                            'guid' => 'cccccccc-cccc-cccc-cccc-ccccccccc011',
                            'order_number' => 'WH-3401',
                            'order_type' => 'warehouse_to_customer',
                            'location_label' => 'Main Warehouse',
                            'customer_name' => 'Northline Hospitality',
                            'source_location_label' => 'Main Warehouse',
                            'destination_location_label' => 'Northline Delivery',
                            'vendor_id' => null,
                            'status' => 'ready_to_receive',
                            'priority' => 1,
                            'item_count' => 1,
                            'order_total' => 499.0,
                            'sales_channel' => 'Customer Delivery',
                            'requested_ship_date' => '2026-03-20',
                            'notes' => 'Northline delivery is on site and ready for customer receipt.'
                        ],
                        'Lookups' => [
                            [
                                'TargetColumn' => 'location_id',
                                'LookupTable' => 'locations',
                                'LookupColumn' => 'guid',
                                'SelectColumn' => 'id',
                                'LookupValue' => '11111111-1111-1111-1111-111111111111'
                            ],
                            [
                                'TargetColumn' => 'source_location_id',
                                'LookupTable' => 'locations',
                                'LookupColumn' => 'guid',
                                'SelectColumn' => 'id',
                                'LookupValue' => '11111111-1111-1111-1111-111111111111'
                            ],
                            [
                                'TargetColumn' => 'destination_location_id',
                                'LookupTable' => 'locations',
                                'LookupColumn' => 'guid',
                                'SelectColumn' => 'id',
                                'LookupValue' => '44444444-4444-4444-4444-444444444444'
                            ]
                        ]
                    ],
                    [
                        'Values' => [
                            'guid' => 'cccccccc-cccc-cccc-cccc-ccccccccc012',
                            'order_number' => 'WH-3402',
                            'order_type' => 'warehouse_to_customer',
                            'location_label' => 'Main Warehouse',
                            'customer_name' => 'Northline Studio',
                            'source_location_label' => 'Main Warehouse',
                            'destination_location_label' => 'Northline Delivery',
                            'vendor_id' => null,
                            'status' => 'received',
                            'priority' => 2,
                            'item_count' => 1,
                            'order_total' => 499.0,
                            'sales_channel' => 'Customer Delivery',
                            'requested_ship_date' => '2026-03-18',
                            'notes' => 'Completed customer delivery cleared through Northline.'
                        ],
                        'Lookups' => [
                            [
                                'TargetColumn' => 'location_id',
                                'LookupTable' => 'locations',
                                'LookupColumn' => 'guid',
                                'SelectColumn' => 'id',
                                'LookupValue' => '11111111-1111-1111-1111-111111111111'
                            ],
                            [
                                'TargetColumn' => 'source_location_id',
                                'LookupTable' => 'locations',
                                'LookupColumn' => 'guid',
                                'SelectColumn' => 'id',
                                'LookupValue' => '11111111-1111-1111-1111-111111111111'
                            ],
                            [
                                'TargetColumn' => 'destination_location_id',
                                'LookupTable' => 'locations',
                                'LookupColumn' => 'guid',
                                'SelectColumn' => 'id',
                                'LookupValue' => '44444444-4444-4444-4444-444444444444'
                            ]
                        ]
                    ],
                    [
                        'Values' => [
                            'guid' => 'cccccccc-cccc-cccc-cccc-ccccccccc013',
                            'order_number' => 'WH-3403',
                            'order_type' => 'warehouse_to_customer',
                            'location_label' => 'Main Warehouse',
                            'customer_name' => 'Northline Design Team',
                            'source_location_label' => 'Main Warehouse',
                            'destination_location_label' => 'Northline Delivery',
                            'vendor_id' => null,
                            'status' => 'received',
                            'priority' => 3,
                            'item_count' => 1,
                            'order_total' => 1899.95,
                            'sales_channel' => 'Customer Delivery',
                            'requested_ship_date' => '2026-03-17',
                            'notes' => 'Large customer delivery completed at the Northline destination.'
                        ],
                        'Lookups' => [
                            [
                                'TargetColumn' => 'location_id',
                                'LookupTable' => 'locations',
                                'LookupColumn' => 'guid',
                                'SelectColumn' => 'id',
                                'LookupValue' => '11111111-1111-1111-1111-111111111111'
                            ],
                            [
                                'TargetColumn' => 'source_location_id',
                                'LookupTable' => 'locations',
                                'LookupColumn' => 'guid',
                                'SelectColumn' => 'id',
                                'LookupValue' => '11111111-1111-1111-1111-111111111111'
                            ],
                            [
                                'TargetColumn' => 'destination_location_id',
                                'LookupTable' => 'locations',
                                'LookupColumn' => 'guid',
                                'SelectColumn' => 'id',
                                'LookupValue' => '44444444-4444-4444-4444-444444444444'
                            ]
                        ]
                    ]
                ]
            ]
        ],
        [
            'seed_name' => 'order_items_default_seed',
            'version' => 1,
            'version_key_name' => 'OrderItemsDefault',
            'file' => 'seeds/order-items.seed.json',
            'definition' => [
                'table_name' => 'order_items',
                'mode' => 'insert_all_if_table_empty',
                'match_columns' => [

                ],
                'patch_columns_when_empty' => [

                ],
                'zero_is_empty_columns' => [

                ],
                'rows' => [
                    [
                        'Values' => [
                            'inventory_instance_guid' => 'bbbbbbbb-bbbb-bbbb-bbbb-bbbbbbbbb001'
                        ],
                        'Lookups' => [
                            [
                                'TargetColumn' => 'order_id',
                                'LookupTable' => 'orders',
                                'LookupColumn' => 'order_number',
                                'SelectColumn' => 'id',
                                'LookupValue' => 'WH-3001'
                            ]
                        ]
                    ],
                    [
                        'Values' => [
                            'inventory_instance_guid' => 'bbbbbbbb-bbbb-bbbb-bbbb-bbbbbbbbb002'
                        ],
                        'Lookups' => [
                            [
                                'TargetColumn' => 'order_id',
                                'LookupTable' => 'orders',
                                'LookupColumn' => 'order_number',
                                'SelectColumn' => 'id',
                                'LookupValue' => 'WH-3002'
                            ]
                        ]
                    ],
                    [
                        'Values' => [
                            'inventory_instance_guid' => 'bbbbbbbb-bbbb-bbbb-bbbb-bbbbbbbbb003'
                        ],
                        'Lookups' => [
                            [
                                'TargetColumn' => 'order_id',
                                'LookupTable' => 'orders',
                                'LookupColumn' => 'order_number',
                                'SelectColumn' => 'id',
                                'LookupValue' => 'TR-4001'
                            ]
                        ]
                    ],
                    [
                        'Values' => [
                            'inventory_instance_guid' => 'bbbbbbbb-bbbb-bbbb-bbbb-bbbbbbbbb004'
                        ],
                        'Lookups' => [
                            [
                                'TargetColumn' => 'order_id',
                                'LookupTable' => 'orders',
                                'LookupColumn' => 'order_number',
                                'SelectColumn' => 'id',
                                'LookupValue' => 'WH-3003'
                            ]
                        ]
                    ],
                    [
                        'Values' => [
                            'inventory_instance_guid' => 'bbbbbbbb-bbbb-bbbb-bbbb-bbbbbbbbb005'
                        ],
                        'Lookups' => [
                            [
                                'TargetColumn' => 'order_id',
                                'LookupTable' => 'orders',
                                'LookupColumn' => 'order_number',
                                'SelectColumn' => 'id',
                                'LookupValue' => 'TR-4101'
                            ]
                        ]
                    ],
                    [
                        'Values' => [
                            'inventory_instance_guid' => 'bbbbbbbb-bbbb-bbbb-bbbb-bbbbbbbbb006'
                        ],
                        'Lookups' => [
                            [
                                'TargetColumn' => 'order_id',
                                'LookupTable' => 'orders',
                                'LookupColumn' => 'order_number',
                                'SelectColumn' => 'id',
                                'LookupValue' => 'TR-4102'
                            ]
                        ]
                    ],
                    [
                        'Values' => [
                            'inventory_instance_guid' => 'bbbbbbbb-bbbb-bbbb-bbbb-bbbbbbbbb007'
                        ],
                        'Lookups' => [
                            [
                                'TargetColumn' => 'order_id',
                                'LookupTable' => 'orders',
                                'LookupColumn' => 'order_number',
                                'SelectColumn' => 'id',
                                'LookupValue' => 'WH-3201'
                            ]
                        ]
                    ],
                    [
                        'Values' => [
                            'inventory_instance_guid' => 'bbbbbbbb-bbbb-bbbb-bbbb-bbbbbbbbb008'
                        ],
                        'Lookups' => [
                            [
                                'TargetColumn' => 'order_id',
                                'LookupTable' => 'orders',
                                'LookupColumn' => 'order_number',
                                'SelectColumn' => 'id',
                                'LookupValue' => 'WH-3301'
                            ]
                        ]
                    ],
                    [
                        'Values' => [
                            'inventory_instance_guid' => 'bbbbbbbb-bbbb-bbbb-bbbb-bbbbbbbbb009'
                        ],
                        'Lookups' => [
                            [
                                'TargetColumn' => 'order_id',
                                'LookupTable' => 'orders',
                                'LookupColumn' => 'order_number',
                                'SelectColumn' => 'id',
                                'LookupValue' => 'TR-4201'
                            ]
                        ]
                    ],
                    [
                        'Values' => [
                            'inventory_instance_guid' => 'bbbbbbbb-bbbb-bbbb-bbbb-bbbbbbbbb010'
                        ],
                        'Lookups' => [
                            [
                                'TargetColumn' => 'order_id',
                                'LookupTable' => 'orders',
                                'LookupColumn' => 'order_number',
                                'SelectColumn' => 'id',
                                'LookupValue' => 'TR-4202'
                            ]
                        ]
                    ],
                    [
                        'Values' => [
                            'inventory_instance_guid' => 'bbbbbbbb-bbbb-bbbb-bbbb-bbbbbbbbb011'
                        ],
                        'Lookups' => [
                            [
                                'TargetColumn' => 'order_id',
                                'LookupTable' => 'orders',
                                'LookupColumn' => 'order_number',
                                'SelectColumn' => 'id',
                                'LookupValue' => 'WH-3401'
                            ]
                        ]
                    ],
                    [
                        'Values' => [
                            'inventory_instance_guid' => 'bbbbbbbb-bbbb-bbbb-bbbb-bbbbbbbbb012'
                        ],
                        'Lookups' => [
                            [
                                'TargetColumn' => 'order_id',
                                'LookupTable' => 'orders',
                                'LookupColumn' => 'order_number',
                                'SelectColumn' => 'id',
                                'LookupValue' => 'WH-3402'
                            ]
                        ]
                    ],
                    [
                        'Values' => [
                            'inventory_instance_guid' => 'bbbbbbbb-bbbb-bbbb-bbbb-bbbbbbbbb013'
                        ],
                        'Lookups' => [
                            [
                                'TargetColumn' => 'order_id',
                                'LookupTable' => 'orders',
                                'LookupColumn' => 'order_number',
                                'SelectColumn' => 'id',
                                'LookupValue' => 'WH-3403'
                            ]
                        ]
                    ]
                ]
            ]
        ]
    ];

    public function run(): void
    {
        foreach ($this->seeds as $seed) {
            $definition = $seed['definition'];
            $table = $definition['table_name'];
            $mode = $definition['mode'];
            $rows = array_map(fn (array $row): array => $this->resolveRow($row), $definition['rows']);

            match ($mode) {
                'ensure_missing_rows' => $this->ensureMissingRows($table, $definition, $rows),
                'patch_existing_when_empty' => $this->patchExistingWhenEmpty($table, $definition, $rows),
                'insert_all_if_table_empty' => $this->insertAllIfTableEmpty($table, $rows),
                default => throw new \RuntimeException('Unsupported generated seed mode: '.$mode),
            };
        }
    }

    protected function ensureMissingRows(string $table, array $definition, array $rows): void
    {
        foreach ($rows as $row) {
            $match = Arr::only($row, $definition['match_columns']);

            if ($match === [] || ! DB::table($table)->where($match)->exists()) {
                DB::table($table)->insert($row);
            }
        }
    }

    protected function patchExistingWhenEmpty(string $table, array $definition, array $rows): void
    {
        foreach ($rows as $row) {
            $match = Arr::only($row, $definition['match_columns']);
            $existing = $match === [] ? null : DB::table($table)->where($match)->first();

            if (! $existing) {
                DB::table($table)->insert($row);
                continue;
            }

            $updates = [];
            foreach ($definition['patch_columns_when_empty'] as $column) {
                $currentValue = $existing->{$column} ?? null;
                $isEmpty = $currentValue === null || $currentValue === '';

                if (in_array($column, $definition['zero_is_empty_columns'], true)) {
                    $isEmpty = $isEmpty || $currentValue === 0 || $currentValue === '0';
                }

                if ($isEmpty && array_key_exists($column, $row)) {
                    $updates[$column] = $row[$column];
                }
            }

            if ($updates !== []) {
                DB::table($table)->where($match)->update($updates);
            }
        }
    }

    protected function insertAllIfTableEmpty(string $table, array $rows): void
    {
        if (DB::table($table)->count() === 0 && $rows !== []) {
            DB::table($table)->insert($rows);
        }
    }

    protected function resolveRow(array $row): array
    {
        $values = $row['Values'] ?? $row['values'] ?? [];

        foreach (($row['Lookups'] ?? $row['lookups'] ?? []) as $lookup) {
            $resolved = DB::table((string) ($lookup['LookupTable'] ?? $lookup['lookupTable']))
                ->where((string) ($lookup['LookupColumn'] ?? $lookup['lookupColumn']), $lookup['LookupValue'] ?? $lookup['lookupValue'])
                ->value((string) ($lookup['SelectColumn'] ?? $lookup['selectColumn']));

            if ($resolved === null && (bool) ($lookup['Required'] ?? $lookup['required'] ?? false)) {
                throw new \RuntimeException('Unable to resolve required seed lookup for '.(string) ($lookup['TargetColumn'] ?? $lookup['targetColumn']));
            }

            $values[(string) ($lookup['TargetColumn'] ?? $lookup['targetColumn'])] = $resolved;
        }

        return $values;
    }
}
