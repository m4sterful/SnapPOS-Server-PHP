<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Query\Expression;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
            Schema::create('inventory_instance', function (Blueprint $table): void {
                $table->id();
                $table->text('stock_type')->nullable();
                $table->text('notes')->nullable();
                $table->char('guid', 36);
                $table->char('location_guid', 36)->nullable();
                $table->char('shipment_guid', 36)->nullable();
                $table->dateTime('last_updated')->nullable()->useCurrent();
                $table->text('serial_number')->nullable();
                $table->char('inventory_guid', 36);
                $table->index(['location_guid'], 'idx_inventory_instance_location_guid');
                $table->index(['shipment_guid'], 'idx_inventory_instance_shipment_guid');
                $table->unique(['guid'], 'uq_inventory_instance_guid');
            });
    }

    public function down(): void
    {
            Schema::dropIfExists('inventory_instance');
    }
};
