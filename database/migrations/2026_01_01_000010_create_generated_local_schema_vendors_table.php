<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Query\Expression;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
            Schema::create('vendors', function (Blueprint $table): void {
                $table->id();
                $table->text('code')->nullable();
                $table->text('vendor_name')->nullable();
                $table->text('country')->nullable();
                $table->text('currency')->nullable();
                $table->char('guid', 36);
                $table->integer('status')->nullable();
                $table->integer('fifo')->nullable();
                $table->integer('lead_time')->nullable();
                $table->integer('shipping_time')->nullable();
                $table->integer('tax_amount')->nullable();
                $table->integer('dropship')->nullable();
                $table->index(['code'], 'idx_vendors_code');
                $table->unique(['code'], 'uq_vendors_code');
            });
    }

    public function down(): void
    {
            Schema::dropIfExists('vendors');
    }
};
