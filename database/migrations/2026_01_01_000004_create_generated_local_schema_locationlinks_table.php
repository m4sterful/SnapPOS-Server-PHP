<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Query\Expression;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
            Schema::create('locationlinks', function (Blueprint $table): void {
                $table->id();
                $table->char('entity_guid', 36);
                $table->char('location_guid', 36);
                $table->text('type');
                $table->index(['entity_guid', 'location_guid'], 'idx_locationlinks_customer_guid');
            });
    }

    public function down(): void
    {
            Schema::dropIfExists('locationlinks');
    }
};
