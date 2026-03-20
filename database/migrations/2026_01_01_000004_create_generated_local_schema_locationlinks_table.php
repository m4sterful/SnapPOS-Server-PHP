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
                $table->char('customer_guid', 36);
                $table->char('location_guid', 36);
                $table->index(['customer_guid'], 'idx_locationlinks_customer_guid');
                $table->index(['location_guid'], 'idx_locationlinks_location_guid');
            });
    }

    public function down(): void
    {
            Schema::dropIfExists('locationlinks');
    }
};
