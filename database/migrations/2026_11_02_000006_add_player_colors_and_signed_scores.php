<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('team_room_progress', fn (Blueprint $table) => $table->integer('awarded_points')->default(0)->change());
        Schema::table('participants', fn (Blueprint $table) => $table->unsignedSmallInteger('color_hue')->nullable());
        // Give existing members distinct, spread-out colors without changing identities or scores.
        foreach (DB::table('participants')->select('team_id')->distinct()->pluck('team_id') as $teamId) {
            $seed = random_int(0, 359);
            foreach (DB::table('participants')->where('team_id', $teamId)->orderBy('id')->pluck('id') as $index => $id) {
                DB::table('participants')->where('id', $id)->update(['color_hue' => ($seed + $index * 137) % 360]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('participants', fn (Blueprint $table) => $table->dropColumn('color_hue'));
        // Keep the score column signed so rollback never corrupts already awarded negative points.
    }
};
