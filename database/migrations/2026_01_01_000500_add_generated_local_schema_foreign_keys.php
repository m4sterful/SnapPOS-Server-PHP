<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
            Schema::disableForeignKeyConstraints();

            Schema::table('locationlinks', function (Blueprint $table): void {
                $table->foreign(['entity_guid'], 'fk_locationlinks_customer_guid')->references(['guid'])->on('contacts')->onDelete('cascade')->onUpdate('cascade');
                $table->foreign(['location_guid'], 'fk_locationlinks_location_guid')->references(['guid'])->on('locations')->onDelete('cascade')->onUpdate('cascade');
            });

            Schema::table('contact_details', function (Blueprint $table): void {
                $table->foreign(['guid'], 'fk_contact_details_guid')->references(['guid'])->on('contacts')->onDelete('cascade')->onUpdate('cascade');
            });

            Schema::table('users', function (Blueprint $table): void {
                $table->foreign(['cust_guid'], 'fk_users_cust_guid')->references(['guid'])->on('contacts')->onDelete('set null')->onUpdate('cascade');
            });

            Schema::table('pricing', function (Blueprint $table): void {
                $table->foreign(['inventory_guid'], 'fk_pricing_inventory_guid')->references(['guid'])->on('inventory_template')->onDelete('restrict')->onUpdate('cascade');
            });

            Schema::table('inventory_instance', function (Blueprint $table): void {
                $table->foreign(['inventory_guid'], 'fk_inventory_instance_inventory_guid')->references(['guid'])->on('inventory_template')->onDelete('restrict')->onUpdate('cascade');
                $table->foreign(['location_guid'], 'fk_inventory_instance_location_guid')->references(['guid'])->on('locations')->onDelete('set null')->onUpdate('cascade');
                $table->foreign(['shipment_guid'], 'fk_inventory_instance_shipment_guid')->references(['guid'])->on('orders')->onDelete('set null')->onUpdate('cascade');
            });

            Schema::table('stocktakesessions', function (Blueprint $table): void {
                $table->foreign(['store_guid'], 'fk_stocktakesessions_store_guid')->references(['guid'])->on('locations')->onDelete('set null')->onUpdate('cascade');
            });

            Schema::table('orders', function (Blueprint $table): void {
                $table->foreign(['location_guid'], 'fk_orders_location_guid')->references(['guid'])->on('locations')->onDelete('restrict')->onUpdate('cascade');
                $table->foreign(['source_location_guid'], 'fk_orders_source_location_guid')->references(['guid'])->on('locations')->onDelete('set null')->onUpdate('cascade');
                $table->foreign(['destination_location_guid'], 'fk_orders_destination_location_guid')->references(['guid'])->on('locations')->onDelete('set null')->onUpdate('cascade');
                $table->foreign(['released_by_user_guid'], 'fk_orders_released_by_user_guid')->references(['guid'])->on('users')->onDelete('no action')->onUpdate('cascade');
            });

            Schema::table('order_items', function (Blueprint $table): void {
                $table->foreign(['order_guid'], 'fk_order_items_order_id')->references(['guid'])->on('orders')->onDelete('cascade')->onUpdate('cascade');
                $table->foreign(['inventory_instance_guid'], 'fk_order_items_inventory_instance_guid')->references(['guid'])->on('inventory_instance')->onDelete('restrict')->onUpdate('cascade');
            });

            Schema::table('truck_schedule_entries', function (Blueprint $table): void {
                $table->foreign(['warehouse_location_id'], 'fk_truck_schedule_entries_warehouse_location_id')->references(['id'])->on('locations')->onDelete('restrict')->onUpdate('cascade');
                $table->foreign(['destination_location_id'], 'fk_truck_schedule_entries_destination_location_id')->references(['id'])->on('locations')->onDelete('restrict')->onUpdate('cascade');
            });

            Schema::enableForeignKeyConstraints();
    }

    public function down(): void
    {
            Schema::disableForeignKeyConstraints();

            Schema::table('truck_schedule_entries', function (Blueprint $table): void {
                $table->dropForeign('fk_truck_schedule_entries_warehouse_location_id');
                $table->dropForeign('fk_truck_schedule_entries_destination_location_id');
            });

            Schema::table('order_items', function (Blueprint $table): void {
                $table->dropForeign('fk_order_items_order_id');
                $table->dropForeign('fk_order_items_inventory_instance_guid');
            });

            Schema::table('orders', function (Blueprint $table): void {
                $table->dropForeign('fk_orders_location_guid');
                $table->dropForeign('fk_orders_source_location_guid');
                $table->dropForeign('fk_orders_destination_location_guid');
                $table->dropForeign('fk_orders_released_by_user_guid');
            });

            Schema::table('stocktakesessions', function (Blueprint $table): void {
                $table->dropForeign('fk_stocktakesessions_store_guid');
            });

            Schema::table('inventory_instance', function (Blueprint $table): void {
                $table->dropForeign('fk_inventory_instance_inventory_guid');
                $table->dropForeign('fk_inventory_instance_location_guid');
                $table->dropForeign('fk_inventory_instance_shipment_guid');
            });

            Schema::table('pricing', function (Blueprint $table): void {
                $table->dropForeign('fk_pricing_inventory_guid');
            });

            Schema::table('users', function (Blueprint $table): void {
                $table->dropForeign('fk_users_cust_guid');
            });

            Schema::table('contact_details', function (Blueprint $table): void {
                $table->dropForeign('fk_contact_details_guid');
            });

            Schema::table('locationlinks', function (Blueprint $table): void {
                $table->dropForeign('fk_locationlinks_customer_guid');
                $table->dropForeign('fk_locationlinks_location_guid');
            });

            Schema::enableForeignKeyConstraints();
    }
};
