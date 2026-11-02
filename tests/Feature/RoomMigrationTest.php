<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class RoomMigrationTest extends TestCase
{
    public function test_existing_sqlite_rooms_teams_and_players_survive_the_ownership_migration(): void
    {
        $originalConnection = DB::getDefaultConnection();
        config(['database.connections.migration_test' => [
            'driver' => 'sqlite', 'database' => ':memory:', 'foreign_key_constraints' => true,
        ]]);
        DB::setDefaultConnection('migration_test');
        Schema::clearResolvedInstance('db.schema');

        try {
            (require database_path('migrations/0001_01_01_000000_create_users_table.php'))->up();
            (require database_path('migrations/2026_10_31_000001_create_game_lobby_tables.php'))->up();
            $teacher = DB::table('users')->insertGetId([
                'name' => 'Profesor', 'email' => 'profe@example.test', 'password' => 'legacy-hash',
            ]);
            $game = DB::table('game_sessions')->insertGetId([
                'teacher_id' => $teacher, 'code' => '123456', 'mode' => 'teams',
                'difficulty' => 'easy', 'duration_minutes' => 10,
            ]);
            $team = DB::table('teams')->insertGetId(['game_session_id' => $game, 'name' => 'Exploradores']);
            DB::table('participants')->insert(['team_id' => $team, 'display_name' => 'Ana']);

            (require database_path('migrations/2026_11_02_000001_allow_rooms_without_teacher_accounts.php'))->up();

            $this->assertTrue(Schema::hasColumn('game_sessions', 'owner_token_hash'));
            $this->assertSame($teacher, DB::table('game_sessions')->where('id', $game)->value('teacher_id'));
            $this->assertSame('Exploradores', DB::table('teams')->where('id', $team)->value('name'));
            $this->assertSame('Ana', DB::table('participants')->where('team_id', $team)->value('display_name'));
            $this->assertSame([], DB::select('PRAGMA foreign_key_check'));

            DB::table('game_sessions')->insert([
                'owner_token_hash' => hash('sha256', 'test-owner'), 'code' => '654321',
                'mode' => 'teams', 'difficulty' => 'easy', 'duration_minutes' => 10,
            ]);
            $this->assertSame(2, DB::table('game_sessions')->count());

            (require database_path('migrations/2026_11_02_000002_add_playable_dungeons.php'))->up();
            $this->assertSame(10, DB::table('game_sessions')->where('id', $game)->value('duration_minutes'));
            $this->assertSame('easy', DB::table('game_sessions')->where('id', $game)->value('difficulty'));
            $this->assertSame('Ana', DB::table('participants')->where('team_id', $team)->value('display_name'));
            $this->assertSame([], DB::select('PRAGMA foreign_key_check'));
            DB::table('game_sessions')->insert([
                'owner_token_hash' => hash('sha256', 'new-owner'), 'code' => '777777',
                'mode' => 'individual', 'difficulty' => 'hard',
            ]);
            $this->assertSame(3, DB::table('game_sessions')->count());
            $this->assertTrue(Schema::hasTable('rooms'));
            $this->assertTrue(Schema::hasTable('team_room_progress'));

            (require database_path('migrations/2026_11_02_000003_add_participant_room_positions.php'))->up();
            $this->assertTrue(Schema::hasColumn('participants', 'current_room_id'));
            (require database_path('migrations/2026_11_02_000004_add_participant_departures.php'))->up();
            $this->assertTrue(Schema::hasColumn('participants', 'left_at'));
            $this->assertNull(DB::table('participants')->where('team_id', $team)->value('left_at'));
            (require database_path('migrations/2026_11_02_000005_create_team_chat_messages.php'))->up();
            $this->assertTrue(Schema::hasTable('team_chat_messages'));
            $room = DB::table('rooms')->insertGetId([
                'game_session_id' => $game, 'position' => 1, 'grid_x' => 0, 'grid_y' => 0,
                'cipher_type' => 'caesar', 'ciphertext' => 'XYZ', 'public_parameters' => '{}', 'solution' => 'legacy-encrypted-value',
            ]);
            $progress = DB::table('team_room_progress')->insertGetId(['team_id' => $team, 'room_id' => $room, 'awarded_points' => 100]);
            (require database_path('migrations/2026_11_02_000006_add_player_colors_and_signed_scores.php'))->up();
            $this->assertNotNull(DB::table('participants')->where('team_id', $team)->value('color_hue'));
            $this->assertSame(100, DB::table('team_room_progress')->where('id', $progress)->value('awarded_points'));
            DB::table('team_room_progress')->where('id', $progress)->update(['awarded_points' => -20]);
            $this->assertSame(-20, DB::table('team_room_progress')->where('id', $progress)->value('awarded_points'));
            $this->assertSame('Ana', DB::table('participants')->where('team_id', $team)->value('display_name'));
            $this->assertNull(DB::table('participants')->where('team_id', $team)->value('current_room_id'));
            $this->assertSame(3, DB::table('game_sessions')->count());
            $this->assertSame([], DB::select('PRAGMA foreign_key_check'));
        } finally {
            DB::purge('migration_test');
            DB::setDefaultConnection($originalConnection);
            Schema::clearResolvedInstance('db.schema');
        }
    }
}
