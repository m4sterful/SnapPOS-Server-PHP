<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Query\Expression;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
            Schema::create('pricetypes', function (Blueprint $table): void {
                $table->id();
            });
    }

    public function down(): void
    {
            Schema::dropIfExists('pricetypes');
    }
};
