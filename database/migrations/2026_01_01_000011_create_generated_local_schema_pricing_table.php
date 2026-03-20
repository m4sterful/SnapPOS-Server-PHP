<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Query\Expression;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
            Schema::create('pricing', function (Blueprint $table): void {
                $table->id();
                $table->char('inventory_guid', 36);
                $table->char('parent_guid', 36)->nullable();
                $table->text('currency')->nullable();
                $table->text('category')->nullable();
                $table->text('comment')->nullable();
                $table->integer('tax_rate_id')->nullable();
                $table->integer('price_type_id')->nullable();
                $table->integer('updated_by_user')->nullable();
                $table->decimal('price', 10, 2)->nullable();
                $table->dateTime('effective_date')->nullable()->useCurrent();
                $table->dateTime('expiry_date')->nullable()->useCurrent();
                $table->dateTime('date_added')->nullable()->useCurrent();
                $table->dateTime('date_updated')->nullable()->useCurrent();
                $table->index(['inventory_guid'], 'idx_pricing_inventory_guid');
                $table->index(['category'], 'idx_pricing_category');
                $table->index(['effective_date'], 'idx_pricing_effective_date');
            });
    }

    public function down(): void
    {
            Schema::dropIfExists('pricing');
    }
};
