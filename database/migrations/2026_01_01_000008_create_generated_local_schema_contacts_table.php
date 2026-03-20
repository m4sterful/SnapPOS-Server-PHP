<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Query\Expression;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
            Schema::create('contacts', function (Blueprint $table): void {
                $table->id();
                $table->char('guid', 36);
                $table->text('first_name')->nullable();
                $table->text('middle_name')->nullable();
                $table->text('last_name')->nullable();
                $table->text('business')->nullable();
                $table->text('note')->nullable();
                $table->text('tag')->nullable();
                $table->integer('modified_by')->nullable();
                $table->dateTime('created_at')->nullable()->useCurrent();
                $table->dateTime('modified_at')->nullable()->useCurrent();
                $table->index(['guid'], 'idx_contacts_guid');
                $table->index(['business'], 'idx_contacts_business');
                $table->index(['first_name'], 'idx_contacts_name');
                $table->index(['last_name'], 'idx_contacts_lname');
                $table->unique(['guid'], 'uq_contacts_guid');
            });
    }

    public function down(): void
    {
            Schema::dropIfExists('contacts');
    }
};
