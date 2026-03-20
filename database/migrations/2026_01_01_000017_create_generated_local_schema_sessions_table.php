<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Query\Expression;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
            Schema::create('sessions', function (Blueprint $table): void {
                $table->id();
                $table->string('token_hash', 64);
                $table->integer('user_id');
                $table->dateTime('created_at')->useCurrent();
                $table->dateTime('expires_at');
                $table->dateTime('last_seen_at')->useCurrent();
                $table->dateTime('revoked_at')->nullable();
                $table->string('client_ip', 64)->nullable();
                $table->string('user_agent', 512)->nullable();
                $table->index(['user_id'], 'idx_sessions_user_id');
                $table->index(['expires_at'], 'idx_sessions_expires_at');
                $table->unique(['token_hash'], 'uq_sessions_token_hash');
            });
    }

    public function down(): void
    {
            Schema::dropIfExists('sessions');
    }
};
