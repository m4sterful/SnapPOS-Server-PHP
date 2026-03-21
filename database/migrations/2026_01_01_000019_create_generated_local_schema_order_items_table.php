<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Query\Expression;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
            Schema::create('order_items', function (Blueprint $table): void {
                $table->id();
                $table->char('order_guid', 36);
                $table->char('inventory_instance_guid', 36);
                $table->dateTime('created_at')->useCurrent();
                $table->unique(['inventory_instance_guid', 'order_guid'], 'uq_order_items_order_instance');
            });
    }

    public function down(): void
    {
            Schema::dropIfExists('order_items');
    }
};
