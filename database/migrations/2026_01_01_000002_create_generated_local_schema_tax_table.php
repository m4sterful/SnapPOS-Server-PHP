<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Query\Expression;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
            Schema::create('tax', function (Blueprint $table): void {
                $table->id();
                $table->text('tax_type')->nullable();
                $table->text('description')->nullable();
                $table->float('rate')->nullable();
            });
    }

    public function down(): void
    {
            Schema::dropIfExists('tax');
    }
};
