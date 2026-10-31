<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('game_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('teacher_id')->constrained('users')->cascadeOnDelete();
            $table->string('code', 6)->unique();
            $table->string('status', 16)->default('lobby');
            $table->string('mode', 16);
            $table->string('difficulty', 16);
            $table->unsignedSmallInteger('duration_minutes');
            $table->timestamp('started_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();
        });

        Schema::create('teams', function (Blueprint $table) {
            $table->id();
            $table->foreignId('game_session_id')->constrained()->cascadeOnDelete();
            $table->string('name', 40);
            $table->timestamps();
            $table->unique(['game_session_id', 'name']);
        });

        Schema::create('participants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->string('display_name', 40);
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamps();
            $table->unique(['team_id', 'display_name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('participants');
        Schema::dropIfExists('teams');
        Schema::dropIfExists('game_sessions');
    }
};
