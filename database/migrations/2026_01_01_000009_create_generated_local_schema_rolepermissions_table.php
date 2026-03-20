<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Query\Expression;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
            Schema::create('rolepermissions', function (Blueprint $table): void {
                $table->id();
                $table->text('api_str');
                $table->integer('role_id');
                $table->index(['api_str'], 'idx_rp_name');
                $table->index(['role_id'], 'idx_rp_id');
                $table->unique(['role_id', 'api_str'], 'uq_rolepermissions_role_id_api_str');
            });
    }

    public function down(): void
    {
            Schema::dropIfExists('rolepermissions');
    }
};
