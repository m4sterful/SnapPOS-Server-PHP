<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Query\Expression;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
            Schema::create('contact_details', function (Blueprint $table): void {
                $table->id();
                $table->char('guid', 36);
                $table->text('phone_number')->nullable();
                $table->text('phone_type')->nullable();
                $table->index(['guid'], 'idx_phone_numbers_guid');
                $table->index(['phone_type'], 'idx_phone_numbers_type');
            });
    }

    public function down(): void
    {
            Schema::dropIfExists('contact_details');
    }
};
