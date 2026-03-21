<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Query\Expression;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
            Schema::create('users', function (Blueprint $table): void {
                $table->id();
                $table->text('username')->nullable();
                $table->text('password_hash')->nullable();
                $table->text('salt')->nullable();
                $table->char('cust_guid', 36)->nullable();
                $table->integer('login_attempts')->nullable();
                $table->integer('active')->nullable();
                $table->integer('permissions')->nullable();
                $table->integer('location')->nullable();
                $table->integer('created_by')->nullable();
                $table->integer('pin')->nullable();
                $table->dateTime('created_at')->nullable()->useCurrent();
                $table->dateTime('last_login')->nullable()->useCurrent();
                $table->dateTime('pwd_last_set')->nullable()->useCurrent();
                $table->char('guid', 36)->nullable();
                $table->index(['username'], 'idx_users_username');
                $table->index(['location'], 'idx_users_location');
                $table->index(['cust_guid'], 'idx_users_cust_guid');
                $table->unique(['username'], 'uq_users_username');
                $table->unique(['guid'], 'uq_users_guid');
            });
    }

    public function down(): void
    {
            Schema::dropIfExists('users');
    }
};
