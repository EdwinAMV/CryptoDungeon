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
        } finally {
            DB::purge('migration_test');
            DB::setDefaultConnection($originalConnection);
            Schema::clearResolvedInstance('db.schema');
        }
    }
}
