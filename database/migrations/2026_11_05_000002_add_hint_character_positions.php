<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hint_usages', function (Blueprint $table) {
            // Null retains the original two-level hints and their historical cost.
            $table->unsignedSmallInteger('character_position')->nullable();
            $table->unique(['team_room_progress_id', 'character_position'], 'hint_progress_character_unique');
        });
    }

    public function down(): void
    {
        Schema::table('hint_usages', function (Blueprint $table) {
            $table->dropUnique('hint_progress_character_unique');
            $table->dropColumn('character_position');
        });
    }
};
