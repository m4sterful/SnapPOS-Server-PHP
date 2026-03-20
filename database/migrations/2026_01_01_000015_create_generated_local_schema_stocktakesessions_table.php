<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Query\Expression;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
            Schema::create('stocktakesessions', function (Blueprint $table): void {
                $table->id();
                $table->char('guid', 36)->nullable();
                $table->text('session_code')->nullable();
                $table->char('store_guid', 36)->nullable();
                $table->text('status')->nullable();
                $table->text('notes')->nullable();
                $table->integer('active')->nullable();
                $table->integer('created_by')->nullable();
                $table->dateTime('last_modified')->nullable()->useCurrent();
                $table->index(['session_code'], 'idx_sessions_code');
                $table->index(['store_guid'], 'idx_sessions_store');
            });
    }

    public function down(): void
    {
            Schema::dropIfExists('stocktakesessions');
    }
};
