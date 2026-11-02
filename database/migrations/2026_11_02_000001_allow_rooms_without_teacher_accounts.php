<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('game_sessions', function (Blueprint $table) {
            // Preserve existing teacher references without requiring new rooms to have one.
            $table->foreignId('teacher_id')->nullable()->change();
            $table->string('owner_token_hash', 64)->nullable()->index();
        });
    }

    public function down(): void
    {
        Schema::table('game_sessions', function (Blueprint $table) {
            $table->dropIndex(['owner_token_hash']);
            $table->dropColumn('owner_token_hash');
            // Keep teacher_id nullable to preserve rooms created without accounts.
        });
    }
};
