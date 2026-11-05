<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hint_usages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_room_progress_id')->constrained('team_room_progress')->cascadeOnDelete();
            $table->foreignId('participant_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('level');
            $table->unsignedSmallInteger('penalty');
            $table->timestamps();
            $table->unique(['team_room_progress_id', 'level']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hint_usages');
    }
};
