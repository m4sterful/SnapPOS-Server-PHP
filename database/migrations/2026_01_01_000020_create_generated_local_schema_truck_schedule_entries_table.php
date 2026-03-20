<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Query\Expression;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
            Schema::create('truck_schedule_entries', function (Blueprint $table): void {
                $table->id();
                $table->integer('warehouse_location_id');
                $table->integer('destination_location_id');
                $table->dateTime('departure_utc');
                $table->string('time_zone_id', 64);
                $table->integer('active');
                $table->text('notes')->nullable();
                $table->index(['warehouse_location_id', 'destination_location_id', 'departure_utc'], 'idx_truck_schedule_entries_lookup');
            });
    }

    public function down(): void
    {
            Schema::dropIfExists('truck_schedule_entries');
    }
};
