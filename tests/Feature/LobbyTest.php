<?php

namespace Tests\Feature;

use App\Enums\GameStatus;
use App\Livewire\Host\SessionCreate;
use App\Livewire\Host\SessionLobby;
use App\Livewire\Player\JoinSession;
use App\Livewire\Player\Lobby;
use App\Models\GameSession;
use App\Services\GameService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Tests\TestCase;

class LobbyTest extends TestCase
{
    use RefreshDatabase;

    private function game(string $mode = 'teams'): GameSession
    {
        return app(GameService::class)->createSession([
            'mode' => $mode,
            'difficulty' => 'easy',
            'duration_minutes' => 30,
        ]);
    }

    public function test_room_pages_are_available_without_an_account(): void
    {
        $this->get(route('rooms.index'))->assertOk();
        $this->get(route('rooms.create'))->assertOk();
        $this->assertGuest();
    }

    public function test_host_can_create_a_configured_room_without_an_account(): void
    {
        Livewire::test(SessionCreate::class)
            ->set('mode', 'individual')->set('difficulty', 'normal')->set('durationMinutes', 45)
            ->call('save')->assertHasNoErrors();

        $game = GameSession::sole();
        $this->assertNull($game->teacher_id);
        $this->assertSame(hash('sha256', session('room_owner_token')), $game->owner_token_hash);
        $this->assertGuest();
        $this->assertMatchesRegularExpression('/^[0-9]{6}$/', $game->code);
        $this->assertSame(GameStatus::Lobby, $game->status);
        $this->assertSame('individual', $game->mode);
        $this->assertSame('normal', $game->difficulty);
        $this->assertSame(45, $game->duration_minutes);
    }

    public function test_invalid_settings_cannot_create_a_session(): void
    {
        Livewire::test(SessionCreate::class)
            ->set('mode', 'unknown')->set('difficulty', 'unknown')->set('durationMinutes', 0)
            ->call('save')->assertHasErrors(['mode', 'difficulty', 'duration_minutes']);

        $this->assertDatabaseCount('game_sessions', 0);
    }

    public function test_empty_duration_gets_a_validation_error(): void
    {
        Livewire::test(SessionCreate::class)
            ->set('durationMinutes', '')->call('save')->assertHasErrors(['duration_minutes']);
    }

    public function test_another_browser_cannot_open_or_modify_the_host_lobby(): void
    {
        $game = $this->game();
        $this->withSession(['room_owner_token' => str_repeat('x', 64)])
            ->get(route('rooms.lobby', $game))->assertForbidden();

        $this->expectException(AuthorizationException::class);
        app(GameService::class)->addTeam($game, 'Intrusos');
    }

    public function test_room_list_only_contains_rooms_created_in_this_browser(): void
    {
        $mine = $this->game();
        $myToken = session('room_owner_token');
        session(['room_owner_token' => str_repeat('x', 64)]);
        $other = $this->game();

        $this->withSession(['room_owner_token' => $myToken])->get(route('rooms.index'))
            ->assertOk()->assertSee($mine->code)->assertDontSee($other->code);
    }

    public function test_host_can_add_teams_and_duplicate_names_are_rejected(): void
    {
        $game = $this->game();
        $component = Livewire::test(SessionLobby::class, ['gameSession' => $game])
            ->set('teamName', '  Criptoequipo  ')->call('addTeam')->assertHasNoErrors()
            ->assertSee('Criptoequipo')->assertSet('teamName', '');

        $component->set('teamName', 'Criptoequipo')->call('addTeam')->assertHasErrors(['teamName']);
        $this->assertDatabaseCount('teams', 1);
    }

    public function test_alumno_can_join_a_team_and_identity_is_saved_in_session(): void
    {
        $game = $this->game();
        $team = $game->teams()->create(['name' => 'Exploradores']);

        Livewire::test(JoinSession::class)->set('code', $game->code)
            ->set('displayName', 'Ana')->set('teamId', $team->id)->call('join')
            ->assertHasNoErrors()->assertRedirect(route('player.lobby'));

        $this->assertDatabaseHas('participants', ['team_id' => $team->id, 'display_name' => 'Ana']);
        $this->assertNotNull(session('participant_id'));
    }

    public function test_individual_mode_creates_an_independent_team(): void
    {
        $game = $this->game('individual');
        $first = app(GameService::class)->joinSession($game->code, 'Ana', null);
        $second = app(GameService::class)->joinSession($game->code, 'Luis', null);

        $this->assertNotSame($first->team_id, $second->team_id);
        $this->assertSame(2, $game->teams()->count());
    }

    public function test_team_from_another_session_is_rejected(): void
    {
        $game = $this->game();
        $foreignTeam = $this->game()->teams()->create(['name' => 'Otro equipo']);

        Livewire::test(JoinSession::class)->set('code', $game->code)
            ->set('displayName', 'Ana')->set('teamId', $foreignTeam->id)->call('join')
            ->assertHasErrors(['teamId']);

        $this->assertDatabaseCount('participants', 0);
    }

    public function test_duplicate_player_names_are_rejected_without_extra_teams(): void
    {
        $game = $this->game('individual');
        app(GameService::class)->joinSession($game->code, 'Ana', null);

        try {
            app(GameService::class)->joinSession($game->code, 'Ana', null);
            $this->fail('A duplicate name was accepted.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('displayName', $exception->errors());
        }

        $this->assertDatabaseCount('teams', 1);
        $this->assertDatabaseCount('participants', 1);
    }

    public function test_unknown_session_code_is_rejected(): void
    {
        Livewire::test(JoinSession::class)->set('code', '123456')->set('displayName', 'Ana')
            ->call('join')->assertHasErrors(['code']);
    }

    public function test_active_and_finished_games_reject_new_players(): void
    {
        foreach ([GameStatus::Active, GameStatus::Finished] as $status) {
            $game = $this->game('individual');
            $game->update(['status' => $status]);

            Livewire::test(JoinSession::class)->set('code', $game->code)->set('displayName', 'Ana')
                ->call('join')->assertHasErrors(['code']);
        }

        $this->assertDatabaseCount('participants', 0);
    }

    public function test_team_creation_is_blocked_after_game_starts(): void
    {
        $game = $this->game();
        $component = Livewire::test(SessionLobby::class, ['gameSession' => $game]);
        $game->update(['status' => GameStatus::Active]);

        $component->set('teamName', 'Tardíos')->call('addTeam')->assertHasErrors(['code']);
        $this->assertDatabaseCount('teams', 0);
    }

    public function test_polling_shows_new_players_to_the_host(): void
    {
        $game = $this->game('individual');
        $component = Livewire::test(SessionLobby::class, ['gameSession' => $game]);
        app(GameService::class)->joinSession($game->code, 'Ana', null);

        $component->call('$refresh')->assertSee('Ana')->assertSee('1 / 100 alumnos');
    }

    public function test_player_lobby_only_shows_own_team_members(): void
    {
        $game = $this->game();
        $firstTeam = $game->teams()->create(['name' => 'Azul']);
        $secondTeam = $game->teams()->create(['name' => 'Rojo']);
        $player = app(GameService::class)->joinSession($game->code, 'Ana', $firstTeam->id);
        app(GameService::class)->joinSession($game->code, 'Compañera', $firstTeam->id);
        app(GameService::class)->joinSession($game->code, 'Jugador privado', $secondTeam->id);

        $this->withSession(['participant_id' => $player->id]);
        Livewire::test(Lobby::class)->assertSee('Compañera')->assertDontSee('Jugador privado');
    }

    public function test_player_lobby_without_a_browser_identity_is_forbidden(): void
    {
        $this->get(route('player.lobby'))->assertForbidden();
    }

    public function test_existing_browser_identity_does_not_create_another_player(): void
    {
        $game = $this->game('individual');
        $player = app(GameService::class)->joinSession($game->code, 'Ana', null);
        $this->withSession(['participant_id' => $player->id]);

        Livewire::test(JoinSession::class)->assertRedirect(route('player.lobby'));
        $this->assertDatabaseCount('participants', 1);
    }

    public function test_join_rate_limit_rejects_excessive_attempts(): void
    {
        for ($i = 0; $i < 150; $i++) {
            RateLimiter::hit('join:127.0.0.1', 60);
        }

        Livewire::test(JoinSession::class)->set('code', '123456')->set('displayName', 'Ana')
            ->call('join')->assertHasErrors(['code'])->assertSee('Demasiados intentos');
    }
}
