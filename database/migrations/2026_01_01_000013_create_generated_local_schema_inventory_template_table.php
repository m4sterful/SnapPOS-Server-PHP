<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Query\Expression;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
            Schema::create('inventory_template', function (Blueprint $table): void {
                $table->id();
                $table->text('product_name')->nullable();
                $table->text('description')->nullable();
                $table->text('ref_part_no')->nullable();
                $table->text('product_family')->nullable();
                $table->text('product_type')->nullable();
                $table->text('related_products')->nullable();
                $table->text('web_link')->nullable();
                $table->text('barcode')->nullable();
                $table->text('additional_info')->nullable();
                $table->text('long_description')->nullable();
                $table->char('guid', 36);
                $table->integer('active')->nullable();
                $table->integer('allow_sales')->nullable();
                $table->integer('allow_por')->nullable();
                $table->integer('allow_oversale')->nullable();
                $table->integer('allow_delivery')->nullable();
                $table->integer('allow_finance')->nullable();
                $table->integer('serialize')->nullable();
                $table->integer('assembly_required')->nullable();
                $table->integer('discontinued')->nullable();
                $table->integer('modified_by')->nullable();
                $table->integer('department')->nullable();
                $table->integer('supplier')->nullable();
                $table->dateTime('last_updated')->nullable()->useCurrent();
                $table->index(['ref_part_no'], 'idx_inventory_ref_part_no');
                $table->index(['barcode'], 'idx_inventory_barcode');
                $table->index(['last_updated'], 'idx_inventory_last_updated');
                $table->unique(['guid'], 'uq_inventory_template_guid');
            });
    }

    public function down(): void
    {
            Schema::dropIfExists('inventory_template');
    }
};
