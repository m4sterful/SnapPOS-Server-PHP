<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Query\Expression;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
            Schema::create('orders', function (Blueprint $table): void {
                $table->id();
                $table->char('guid', 36);
                $table->string('order_number', 64);
                $table->char('location_guid', 36);
                $table->string('order_type', 32);
                $table->string('location_label', 128);
                $table->string('customer_name', 128);
                $table->char('source_location_guid', 36)->nullable();
                $table->string('source_location_label', 128)->nullable();
                $table->char('destination_location_guid', 36)->nullable();
                $table->string('destination_location_label', 128)->nullable();
                $table->char('vendor_guid', 36)->nullable();
                $table->string('status', 32);
                $table->integer('priority');
                $table->integer('item_count');
                $table->decimal('order_total', 10, 2);
                $table->string('sales_channel', 64)->nullable();
                $table->date('requested_ship_date')->nullable();
                $table->text('notes')->nullable();
                $table->dateTime('released_at_utc')->nullable();
                $table->integer('released_by_user_guid')->nullable();
                $table->string('release_mode', 32)->nullable();
                $table->text('release_note')->nullable();
                $table->dateTime('created_at')->useCurrent();
                $table->dateTime('updated_at')->useCurrent();
                $table->index(['location_guid'], 'idx_orders_location_id');
                $table->index(['order_type'], 'idx_orders_type');
                $table->index(['status'], 'idx_orders_status');
                $table->index(['destination_location_guid'], 'idx_orders_destination_location_id');
                $table->index(['requested_ship_date'], 'idx_orders_requested_ship_date');
                $table->unique(['guid'], 'uq_orders_guid');
                $table->unique(['order_number'], 'uq_orders_order_number');
            });
    }

    public function down(): void
    {
            Schema::dropIfExists('orders');
    }
};
