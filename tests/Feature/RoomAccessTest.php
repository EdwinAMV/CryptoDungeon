<?php

namespace Tests\Feature;

use App\Livewire\Host\SessionCreate;
use App\Livewire\Host\SessionLobby;
use App\Livewire\Player\JoinSession;
use App\Models\GameSession;
use App\Services\GameService;
use App\Services\RoomAccess;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Features\SupportLockedProperties\CannotUpdateLockedPropertyException;
use Livewire\Livewire;
use Tests\TestCase;

class RoomAccessTest extends TestCase
{
    use RefreshDatabase;

    private function game(): GameSession
    {
        return app(GameService::class)->createSession([
            'mode' => 'teams', 'difficulty' => 'easy', 'duration_minutes' => 10,
        ]);
    }

    public function test_guest_host_can_create_a_room_return_to_it_and_add_groups(): void
    {
        Livewire::test(SessionCreate::class)->set('durationMinutes', 10)
            ->call('save')->assertHasNoErrors();

        $game = GameSession::sole();
        $token = session('room_owner_token');
        $this->assertGuest();
        $this->assertSame(64, strlen($token));

        $this->get(route('rooms.lobby', $game))->assertOk()->assertSee($game->code);
        Livewire::test(SessionLobby::class, ['gameSession' => $game])
            ->set('teamName', 'Exploradores')->call('addTeam')->assertHasNoErrors();
        $this->get(route('rooms.lobby', $game))->assertOk()->assertSee('Exploradores');
        $this->assertSame($token, session('room_owner_token'));
        $this->assertDatabaseCount('users', 0);
    }

    public function test_several_rooms_in_one_browser_share_the_same_owner(): void
    {
        $first = $this->game();
        $second = $this->game();

        $this->assertSame($first->owner_token_hash, $second->owner_token_hash);
        $this->get(route('rooms.index'))->assertOk()->assertSee($first->code)->assertSee($second->code);
    }

    public function test_joining_with_the_public_code_does_not_grant_management_permissions(): void
    {
        $game = $this->game();
        $team = $game->teams()->create(['name' => 'Exploradores']);
        session()->forget('room_owner_token');

        Livewire::test(JoinSession::class)->set('code', $game->code)
            ->set('displayName', 'Ana')->set('teamId', $team->id)->call('join')->assertHasNoErrors();

        $this->assertFalse(app(RoomAccess::class)->canManage($game));
        $this->get(route('rooms.lobby', $game))->assertForbidden();
        $this->get(route('rooms.index'))->assertOk()->assertDontSee($game->code);
    }

    public function test_lost_browser_identity_cannot_be_recovered_using_only_the_room_id(): void
    {
        $game = $this->game();
        session()->forget('room_owner_token');

        $this->get(route('rooms.lobby', $game))->assertForbidden();
        Livewire::test(SessionLobby::class, ['gameSession' => $game])->assertForbidden();
    }

    public function test_host_permission_is_rechecked_on_livewire_actions(): void
    {
        $game = $this->game();
        $component = Livewire::test(SessionLobby::class, ['gameSession' => $game])
            ->set('teamName', 'Intrusos');
        session(['room_owner_token' => str_repeat('x', 64)]);

        $component->call('addTeam')->assertForbidden();
        $this->assertDatabaseCount('teams', 0);
    }

    public function test_host_permission_is_rechecked_when_polling(): void
    {
        $game = $this->game();
        $component = Livewire::test(SessionLobby::class, ['gameSession' => $game]);
        session()->forget('room_owner_token');

        $component->call('$refresh')->assertForbidden();
    }

    public function test_client_cannot_switch_the_host_component_to_another_room(): void
    {
        $game = $this->game();
        $other = $this->game();
        $component = Livewire::test(SessionLobby::class, ['gameSession' => $game]);

        $this->expectException(CannotUpdateLockedPropertyException::class);
        $component->set('gameSessionId', $other->id);
    }

    public function test_owner_credentials_are_hidden_from_models_html_and_livewire_snapshots(): void
    {
        $game = $this->game();
        $token = session('room_owner_token');

        $this->assertArrayNotHasKey('owner_token_hash', $game->toArray());
        $this->get(route('rooms.lobby', $game))->assertOk()
            ->assertDontSee($token)->assertDontSee($game->owner_token_hash);
        Livewire::test(SessionLobby::class, ['gameSession' => $game])
            ->assertDontSee($token)->assertDontSee($game->owner_token_hash);
    }

    public function test_legacy_rooms_without_an_owner_are_not_shown_to_guests(): void
    {
        $game = $this->game();
        $game->update(['owner_token_hash' => null]);
        session()->forget('room_owner_token');

        $this->get(route('rooms.index'))->assertOk()->assertDontSee($game->code);
        $this->get(route('rooms.lobby', $game))->assertForbidden();
    }

    public function test_leaving_as_a_participant_keeps_the_hosts_room_permissions(): void
    {
        $game = $this->game();
        $team = $game->teams()->create(['name' => 'Exploradores']);
        $player = app(GameService::class)->joinSession($game->code, 'Ana', $team->id);
        $token = session('room_owner_token');

        $this->withSession(['participant_id' => $player->id])->post(route('player.leave'))
            ->assertRedirect(route('home'))->assertSessionMissing('participant_id')
            ->assertSessionHas('room_owner_token', $token);

        $this->get(route('rooms.lobby', $game))->assertOk();
    }

    public function test_room_creation_is_rate_limited(): void
    {
        for ($i = 0; $i < 20; $i++) {
            RateLimiter::hit('create-room:127.0.0.1', 60);
        }

        Livewire::test(SessionCreate::class)->call('save')->assertHasErrors(['mode']);
        $this->assertDatabaseCount('game_sessions', 0);
    }

    public function test_old_login_and_registration_endpoints_are_removed(): void
    {
        foreach (['/profesor/acceder', '/profesor/registro'] as $url) {
            $this->get($url)->assertNotFound();
            $this->post($url)->assertNotFound();
        }
    }
}
