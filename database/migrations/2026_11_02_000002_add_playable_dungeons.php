<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('game_sessions', function (Blueprint $table) {
            // Preserve legacy duration values during rollout; new games have no timer.
            $table->unsignedSmallInteger('duration_minutes')->nullable()->change();
            $table->json('map_layout')->nullable();
        });
        Schema::table('teams', function (Blueprint $table) {
            $table->timestamp('finished_at')->nullable();
        });
        Schema::create('rooms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('game_session_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('position');
            $table->unsignedSmallInteger('grid_x');
            $table->unsignedSmallInteger('grid_y');
            $table->string('cipher_type', 32);
            $table->text('ciphertext');
            $table->json('public_parameters');
            $table->text('solution');
            $table->timestamps();
            $table->unique(['game_session_id', 'position']);
            $table->unique(['game_session_id', 'grid_x', 'grid_y']);
        });
        Schema::create('team_room_progress', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->foreignId('room_id')->constrained()->cascadeOnDelete();
            $table->timestamp('unlocked_at')->nullable();
            $table->timestamp('solved_at')->nullable();
            $table->unsignedInteger('awarded_points')->default(0);
            $table->timestamps();
            $table->unique(['team_id', 'room_id']);
        });
        Schema::create('answer_attempts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_room_progress_id')->constrained('team_room_progress')->cascadeOnDelete();
            $table->foreignId('participant_id')->constrained()->cascadeOnDelete();
            $table->boolean('is_correct');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('answer_attempts');
        Schema::dropIfExists('team_room_progress');
        Schema::dropIfExists('rooms');
        Schema::table('teams', fn (Blueprint $table) => $table->dropColumn('finished_at'));
        Schema::table('game_sessions', fn (Blueprint $table) => $table->dropColumn('map_layout'));
    }
};
