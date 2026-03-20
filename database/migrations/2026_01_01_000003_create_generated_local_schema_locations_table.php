<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Query\Expression;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
            Schema::create('locations', function (Blueprint $table): void {
                $table->id();
                $table->char('guid', 36);
                $table->text('location_name')->nullable();
                $table->text('address_line1')->nullable();
                $table->text('address_line2')->nullable();
                $table->text('city')->nullable();
                $table->text('province')->nullable();
                $table->text('postal_code')->nullable();
                $table->text('country')->nullable();
                $table->text('location_type')->nullable();
                $table->text('tag')->nullable();
                $table->index(['guid'], 'idx_locations_guid');
                $table->index(['location_name'], 'idx_locations_name');
                $table->index(['location_type'], 'idx_locations_type');
                $table->index(['postal_code'], 'idx_locations_postal');
                $table->unique(['guid'], 'uq_locations_guid');
            });
    }

    public function down(): void
    {
            Schema::dropIfExists('locations');
    }
};
