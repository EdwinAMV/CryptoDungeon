<?php

namespace Tests\Feature;

use App\Enums\GameStatus;
use App\Livewire\Host\SessionLobby;
use App\Livewire\Player\Lobby;
use App\Livewire\Player\TeamChat;
use App\Models\TeamChatMessage;
use App\Services\GameService;
use App\Services\TeamChatService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ScoringAndChatTest extends TestCase
{
    use RefreshDatabase;

    private function game(string $mode = 'teams'): array
    {
        $games = app(GameService::class);
        $game = $games->createSession(['mode' => $mode, 'difficulty' => 'easy']);
        $blue = $mode === 'teams' ? $games->addTeam($game, 'Azul')->id : null;
        $red = $mode === 'teams' ? $games->addTeam($game, 'Rojo')->id : null;
        $first = $games->joinSession($game->code, 'Ana', $blue);
        $second = $games->joinSession($game->code, 'Luis', $red);

        return [$game, $first, $second];
    }

    public function test_errors_reduce_only_the_current_teams_room_reward_and_duplicates_do_not_count(): void
    {
        [$game, $first, $rival] = $this->game();
        $games = app(GameService::class);
        $companion = $games->joinSession($game->code, 'Eva', $first->team_id);
        $games->startSession($game);
        $room = $game->rooms()->first();
        foreach ([$first, $companion] as $player) {
            session(['participant_id' => $player->id]);
            $this->assertSame('incorrect', $games->submitAnswer($player, $room->id, 'incorrecto'));
        }
        $this->assertSame('correct', $games->submitAnswer($companion, $room->id, $room->solution));
        $this->assertSame('already_solved', $games->submitAnswer($companion, $room->id, $room->solution));
        $this->assertSame(80, $first->team->progress()->sum('awarded_points'));
        $this->assertSame(3, $first->team->attempts()->count());
        $this->assertSame(1, $first->attempts()->count());
        $this->assertSame(2, $companion->attempts()->count());
        session(['participant_id' => $rival->id]);
        $games->submitAnswer($rival, $room->id, $room->solution);
        $this->assertSame(100, $rival->team->progress()->sum('awarded_points'));
        $next = $game->rooms()->where('position', 2)->first();
        $games->submitAnswer($rival, $next->id, $next->solution);
        $this->assertSame(200, $rival->team->progress()->sum('awarded_points'));
    }

    public function test_many_errors_never_create_negative_points_and_results_include_attempts(): void
    {
        [$game, $player] = $this->game('individual');
        $games = app(GameService::class);
        $games->startSession($game);
        session(['participant_id' => $player->id]);
        $first = $game->rooms()->first();
        for ($index = 0; $index < 12; $index++) {
            $games->submitAnswer($player, $first->id, 'incorrecto');
        }
        Livewire::test(Lobby::class)->assertViewHas('availablePoints', 0)->assertViewHas('roomIncorrectCount', 12);
        foreach ($game->rooms as $room) {
            $games->submitAnswer($player, $room->id, $room->solution);
        }
        $this->assertSame(400, $player->team->progress()->sum('awarded_points'));
        Livewire::test(Lobby::class)->assertSee('Resumen de intentos')->assertSee('Por jugador')
            ->assertViewHas('attemptCount', 17)->assertViewHas('incorrectCount', 12)
            ->assertViewHas('roomResults', fn ($rows) => $rows[0]['attempts'] === 13 && $rows[0]['points'] === 0);
        Livewire::test(SessionLobby::class, ['gameSession' => $game])->assertViewHas('game', function ($game) use ($player) {
            $team = $game->teams->find($player->team_id);

            return $team->attempt_count === 17 && $team->incorrect_count === 12;
        });
    }

    public function test_chat_is_private_to_the_team_and_polling_receives_new_messages(): void
    {
        [$game, $first, $rival] = $this->game();
        $companion = app(GameService::class)->joinSession($game->code, 'Eva', $first->team_id);
        session(['participant_id' => $first->id]);
        Livewire::test(TeamChat::class)->set('message', '  Mensaje del azul  ')->call('send')
            ->assertHasNoErrors()->assertSet('message', '')->assertSee('Mensaje del azul');
        session(['participant_id' => $rival->id]);
        Livewire::test(TeamChat::class)->assertDontSee('Mensaje del azul')
            ->set('message', 'Mensaje del rojo')->call('send')->assertHasNoErrors();
        session(['participant_id' => $companion->id]);
        $chat = Livewire::test(TeamChat::class)->assertSee('Mensaje del azul')->assertDontSee('Mensaje del rojo');
        app(TeamChatService::class)->send('Nuevo mensaje');
        $chat->set('message', 'Borrador sin enviar')->call('$refresh')
            ->assertSee('Nuevo mensaje')->assertSet('message', 'Borrador sin enviar');
        $this->assertDatabaseHas('team_chat_messages', ['team_id' => $companion->team_id, 'participant_id' => $companion->id, 'body' => 'Nuevo mensaje']);
        Livewire::test(Lobby::class)->assertSeeLivewire(TeamChat::class);
        app(GameService::class)->startSession($game);
        Livewire::test(Lobby::class)->assertSeeLivewire(TeamChat::class);
        $this->assertSame(GameStatus::Active, $game->fresh()->status);
    }

    public function test_chat_rejects_individual_mode_and_departed_players_and_is_not_shown_to_hosts(): void
    {
        [$game, $first] = $this->game('individual');
        session(['participant_id' => $first->id]);
        Livewire::test(Lobby::class)->assertDontSeeLivewire(TeamChat::class);
        Livewire::test(TeamChat::class)->set('message', 'No permitido')->call('send')->assertForbidden();
        [$group, $member] = $this->game();
        app(GameService::class)->startSession($group);
        session(['participant_id' => $member->id]);
        $chat = Livewire::test(TeamChat::class);
        app(GameService::class)->leaveSession($member);
        $chat->call('$refresh')->assertSee('El chat del equipo ya no está disponible');
        $chat->set('message', 'No permitido')->call('send')->assertForbidden();
        Livewire::test(SessionLobby::class, ['gameSession' => $group])->assertDontSeeLivewire(TeamChat::class);
        session()->forget('participant_id');
        Livewire::test(TeamChat::class)->set('message', 'No permitido')->call('send')->assertForbidden();
        $this->assertDatabaseCount('team_chat_messages', 0);
    }

    public function test_chat_escapes_content_validates_length_and_limits_sending(): void
    {
        [$game, $player] = $this->game();
        session(['participant_id' => $player->id]);
        $chat = Livewire::test(TeamChat::class);
        $chat->set('message', '   ')->call('send')->assertHasErrors(['message']);
        $chat->set('message', str_repeat('a', 501))->call('send')->assertHasErrors(['message']);
        $payload = '<script>alert("test")</script>';
        $chat->set('message', $payload)->call('send')->assertHasNoErrors()
            ->assertSee($payload)->assertDontSee($payload, false);
        for ($index = 0; $index < 9; $index++) {
            app(TeamChatService::class)->send('Mensaje '.$index);
        }
        $chat->set('message', 'Exceso')->call('send')->assertHasErrors(['message']);
        $this->assertDatabaseCount('team_chat_messages', 10);
    }

    public function test_only_last_fifty_messages_are_loaded_and_room_deletion_cascades_chat(): void
    {
        [$game, $player] = $this->game();
        for ($index = 0; $index < 55; $index++) {
            TeamChatMessage::create(['team_id' => $player->team_id, 'participant_id' => $player->id, 'body' => 'Mensaje '.$index]);
        }
        session(['participant_id' => $player->id]);
        $messages = app(TeamChatService::class)->messages();
        $this->assertCount(50, $messages);
        $this->assertSame('Mensaje 5', $messages->first()->body);
        $this->assertSame('Mensaje 54', $messages->last()->body);
        app(GameService::class)->deleteSession($game);
        $this->assertDatabaseCount('team_chat_messages', 0);
        Livewire::test(TeamChat::class)->assertSee('El chat del equipo ya no está disponible');
    }
}
