<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Query\Expression;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
            Schema::create('stocktakeentries', function (Blueprint $table): void {
                $table->id();
                $table->text('inventory_ref')->nullable();
                $table->text('note')->nullable();
                $table->integer('session_id')->nullable();
                $table->integer('user_id')->nullable();
                $table->integer('count')->nullable();
                $table->integer('inventory_id')->nullable();
                $table->dateTime('last_modified')->nullable()->useCurrent();
            });
    }

    public function down(): void
    {
            Schema::dropIfExists('stocktakeentries');
    }
};
