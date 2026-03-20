<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Query\Expression;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
            Schema::create('userroles', function (Blueprint $table): void {
                $table->id();
                $table->text('name')->nullable();
                $table->index(['name'], 'idx_userroles_name');
                $table->unique(['name'], 'uq_userroles_name');
            });
    }

    public function down(): void
    {
            Schema::dropIfExists('userroles');
    }
};
