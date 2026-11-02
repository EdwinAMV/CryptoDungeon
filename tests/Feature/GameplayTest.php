<?php

namespace Tests\Feature;

use App\Enums\GameStatus;
use App\Livewire\Host\SessionLobby;
use App\Livewire\Player\Lobby;
use App\Services\GameService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class GameplayTest extends TestCase
{
    use RefreshDatabase;

    private function readyGame(string $difficulty = 'easy', string $mode = 'teams'): array
    {
        $service = app(GameService::class);
        $game = $service->createSession(['mode' => $mode, 'difficulty' => $difficulty]);
        $players = [];
        foreach (['Azul', 'Rojo'] as $index => $name) {
            $teamId = $mode === 'teams' ? $service->addTeam($game, $name)->id : null;
            $players[] = $service->joinSession($game->code, 'Jugador '.($index + 1), $teamId);
        }

        return [$game, $players];
    }

    public function test_individual_game_needs_two_currently_connected_players(): void
    {
        [$game, $players] = $this->readyGame(mode: 'individual');
        $players[1]->update(['last_seen_at' => now()->subMinutes(2)]);

        Livewire::test(SessionLobby::class, ['gameSession' => $game])->call('start')->assertHasErrors(['start']);
        $this->assertDatabaseCount('rooms', 5);
        $this->assertDatabaseCount('team_room_progress', 0);
        $players[1]->update(['last_seen_at' => now()]);
        Livewire::test(SessionLobby::class, ['gameSession' => $game])->call('start')->assertHasNoErrors();
        $this->assertSame(GameStatus::Active, $game->fresh()->status);
    }

    public function test_each_of_at_least_two_teams_needs_a_connected_player(): void
    {
        [$game, $players] = $this->readyGame();
        app(GameService::class)->addTeam($game, 'Verde');

        Livewire::test(SessionLobby::class, ['gameSession' => $game])->call('start')->assertHasErrors(['start']);
        $team = $game->teams()->where('name', 'Verde')->firstOrFail();
        app(GameService::class)->joinSession($game->code, 'Jugador 3', $team->id);
        Livewire::test(SessionLobby::class, ['gameSession' => $game])->call('start')->assertHasNoErrors();
    }

    public function test_one_team_cannot_start_even_with_two_players(): void
    {
        [$game, $players] = $this->readyGame();
        $players[1]->team->delete();
        app(GameService::class)->joinSession($game->code, 'Otro jugador', $players[0]->team_id);

        Livewire::test(SessionLobby::class, ['gameSession' => $game])->call('start')->assertHasErrors(['start']);
    }

    public function test_all_difficulties_generate_the_right_count_and_all_three_ciphers_without_a_timer(): void
    {
        foreach (['easy' => 5, 'normal' => 10, 'hard' => 15] as $difficulty => $count) {
            [$game] = $this->readyGame($difficulty);
            app(GameService::class)->startSession($game);
            $game->refresh();

            $this->assertCount($count, $game->rooms);
            $this->assertSame(3, $game->rooms->pluck('cipher_type')->unique()->count());
            $this->assertNull($game->ends_at);
            $this->assertNull($game->duration_minutes);
            $this->assertNotNull($game->started_at);
            $this->assertCount($count, $game->rooms->pluck('solution')->unique());
        }
    }

    public function test_only_the_host_can_start_the_game(): void
    {
        [$game] = $this->readyGame();
        session()->forget('room_owner_token');

        Livewire::test(SessionLobby::class, ['gameSession' => $game])->assertForbidden();
        $this->expectException(AuthorizationException::class);
        app(GameService::class)->startSession($game);
    }

    public function test_starting_twice_keeps_the_same_rooms_and_start_time(): void
    {
        [$game] = $this->readyGame();
        $service = app(GameService::class);
        $first = $service->startSession($game);
        $ids = $first->rooms->modelKeys();
        $this->travel(2)->minutes();
        $second = $service->startSession($game);

        $this->assertSame($ids, $second->rooms->modelKeys());
        $this->assertTrue($first->started_at->equalTo($second->started_at));
    }

    public function test_correct_answer_unlocks_only_the_next_room_of_its_own_team(): void
    {
        [$game, $players] = $this->readyGame();
        app(GameService::class)->startSession($game);
        $room = $game->rooms()->first();
        session(['participant_id' => $players[0]->id]);

        $this->assertSame('correct', app(GameService::class)->submitAnswer($players[0], $room->id, $room->solution));
        $this->assertSame(100, $players[0]->team->progress()->sum('awarded_points'));
        $this->assertSame(2, $players[0]->team->progress()->whereNotNull('unlocked_at')->count());
        $this->assertSame(0, $players[1]->team->progress()->whereNotNull('solved_at')->count());
        $this->assertSame(1, $players[1]->team->progress()->whereNotNull('unlocked_at')->count());
    }

    public function test_wrong_answers_do_not_unlock_rooms_or_award_points(): void
    {
        [$game, $players] = $this->readyGame();
        app(GameService::class)->startSession($game);
        session(['participant_id' => $players[0]->id]);
        $room = $game->rooms()->first();

        $this->assertSame('incorrect', app(GameService::class)->submitAnswer($players[0], $room->id, 'INCORRECTO'));
        $this->assertSame(0, $players[0]->team->progress()->sum('awarded_points'));
        $this->assertSame(1, $players[0]->team->progress()->whereNotNull('unlocked_at')->count());
        $this->assertDatabaseHas('answer_attempts', ['participant_id' => $players[0]->id, 'is_correct' => false]);
    }

    public function test_a_locked_future_room_cannot_be_solved_directly(): void
    {
        [$game, $players] = $this->readyGame();
        app(GameService::class)->startSession($game);
        session(['participant_id' => $players[0]->id]);
        $room = $game->rooms()->where('position', 3)->first();

        $this->expectException(HttpException::class);
        app(GameService::class)->submitAnswer($players[0], $room->id, $room->solution);
    }

    public function test_repeated_team_submissions_do_not_duplicate_points_or_attempts(): void
    {
        [$game, $players] = $this->readyGame();
        $companion = app(GameService::class)->joinSession($game->code, 'Compañero', $players[0]->team_id);
        app(GameService::class)->startSession($game);
        $room = $game->rooms()->first();
        session(['participant_id' => $players[0]->id]);
        app(GameService::class)->submitAnswer($players[0], $room->id, $room->solution);
        session(['participant_id' => $companion->id]);

        $this->assertSame('already_solved', app(GameService::class)->submitAnswer($companion, $room->id, $room->solution));
        $this->assertDatabaseCount('answer_attempts', 1);
        $this->assertSame(100, $companion->team->progress()->sum('awarded_points'));
    }

    public function test_solutions_are_encrypted_in_storage_and_absent_from_player_payloads(): void
    {
        [$game, $players] = $this->readyGame();
        app(GameService::class)->startSession($game);
        session(['participant_id' => $players[0]->id]);
        $component = Livewire::test(Lobby::class)->assertSee('Mensaje descifrado');

        foreach ($game->rooms as $room) {
            $this->assertArrayNotHasKey('solution', $room->toArray());
            $this->assertNotSame($room->solution, DB::table('rooms')->where('id', $room->id)->value('solution'));
            $component->assertDontSee($room->solution);
        }
        $this->assertSame('correct', app(GameService::class)->submitAnswer($players[0], $game->rooms->first()->id, $game->rooms->first()->solution));
    }

    public function test_polling_transitions_the_player_from_lobby_to_puzzle_and_submitting_advances(): void
    {
        [$game, $players] = $this->readyGame();
        session(['participant_id' => $players[0]->id]);
        $component = Livewire::test(Lobby::class)->assertSee('Esperando al anfitrión');
        app(GameService::class)->startSession($game);
        $room = $game->rooms()->first();

        $component->call('$refresh')->assertSee('Mensaje descifrado')
            ->assertSet('roomId', $room->id)->set('answer', $room->solution)->call('submit')->assertHasNoErrors();
        $this->assertSame(1, $players[0]->team->progress()->whereNotNull('solved_at')->count());
    }

    public function test_the_game_finishes_only_after_all_teams_finish(): void
    {
        [$game, $players] = $this->readyGame();
        $service = app(GameService::class);
        $service->startSession($game);
        foreach ($players as $index => $player) {
            session(['participant_id' => $player->id]);
            foreach ($game->rooms as $room) {
                $this->assertSame('correct', $service->submitAnswer($player, $room->id, $room->solution));
            }
            $this->assertNotNull($player->team->fresh()->finished_at);
            $this->assertSame($index === 0 ? GameStatus::Active : GameStatus::Finished, $game->fresh()->status);
        }
        Livewire::test(Lobby::class)->assertSee('¡Partida completada!');
    }

    public function test_a_session_cannot_submit_an_answer_as_a_different_participant(): void
    {
        [$game, $players] = $this->readyGame();
        app(GameService::class)->startSession($game);
        session(['participant_id' => $players[0]->id]);
        $room = $game->rooms()->first();

        $this->expectException(HttpException::class);
        app(GameService::class)->submitAnswer($players[1], $room->id, $room->solution);
    }
}
