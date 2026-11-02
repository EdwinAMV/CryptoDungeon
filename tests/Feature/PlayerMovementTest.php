<?php

namespace Tests\Feature;

use App\Livewire\Host\SessionLobby;
use App\Livewire\Player\Lobby;
use App\Services\DungeonMapService;
use App\Services\GameService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class PlayerMovementTest extends TestCase
{
    use RefreshDatabase;

    private function expedition(): array
    {
        $games = app(GameService::class);
        $game = $games->createSession(['mode' => 'teams', 'difficulty' => 'easy']);
        $blue = $games->addTeam($game, 'Azul');
        $red = $games->addTeam($game, 'Rojo');
        $player = $games->joinSession($game->code, 'Explorador', $blue->id);
        $companion = $games->joinSession($game->code, 'Compañera', $blue->id);
        $rival = $games->joinSession($game->code, 'Rival', $red->id);
        $games->startSession($game);
        session(['participant_id' => $player->id]);

        return [$game, $player, $companion, $rival, $game->rooms()->get()];
    }

    public function test_positions_are_individual_and_the_host_refreshes_them_without_solutions(): void
    {
        [$game, $player, $companion, $rival, $rooms] = $this->expedition();
        $host = Livewire::test(SessionLobby::class, ['gameSession' => $game]);
        $this->assertSame($rooms[0]->id, $companion->fresh()->current_room_id);
        app(GameService::class)->submitAnswer($player, $rooms[0]->id, $rooms[0]->solution);
        $host->call('$refresh')->assertViewHas('map', function ($map) use ($player, $companion, $rival) {
            $this->assertContains($player->id, array_column($map['rooms'][1]['players'], 'id'));
            $this->assertEqualsCanonicalizing([$companion->id, $rival->id], array_column($map['rooms'][0]['players'], 'id'));

            return true;
        });
        foreach ($rooms as $room) {
            $host->assertDontSee($room->solution);
        }
        Livewire::test(Lobby::class)->assertViewHas('map', function ($map) use ($rival) {
            $markers = array_merge(...array_column($map['rooms'], 'players'));
            $this->assertNotContains($rival->id, array_column($markers, 'id'));

            return true;
        });
    }

    public function test_a_companion_can_walk_to_a_newly_unlocked_room_and_back(): void
    {
        [$game, $player, $companion, $rival, $rooms] = $this->expedition();
        app(GameService::class)->submitAnswer($player, $rooms[0]->id, $rooms[0]->solution);
        session(['participant_id' => $companion->id]);
        Livewire::test(Lobby::class)->assertSee('Habitación 1 completada')
            ->call('moveTo', $rooms[1]->id)->assertHasNoErrors()->assertSet('roomId', $rooms[1]->id)
            ->call('moveTo', $rooms[0]->id)->assertHasNoErrors()->assertSet('roomId', null);
        $this->assertSame($rooms[0]->id, $companion->fresh()->current_room_id);
        $this->assertSame($rooms[1]->id, $player->fresh()->current_room_id);
        $this->assertSame($rooms[0]->id, $rival->fresh()->current_room_id);
    }

    public function test_unlocked_rooms_cannot_be_skipped(): void
    {
        [$game, $player, $companion, $rival, $rooms] = $this->expedition();
        foreach ([$rooms[0], $rooms[1]] as $room) {
            app(GameService::class)->submitAnswer($player, $room->id, $room->solution);
        }
        session(['participant_id' => $companion->id]);
        Livewire::test(Lobby::class)->call('moveTo', $rooms[2]->id)->assertHasErrors(['movement']);
        $this->assertSame($rooms[0]->id, $companion->fresh()->current_room_id);
    }

    public function test_rooms_unlocked_by_another_team_remain_inaccessible(): void
    {
        [$game, $player, $companion, $rival, $rooms] = $this->expedition();
        app(GameService::class)->submitAnswer($player, $rooms[0]->id, $rooms[0]->solution);
        session(['participant_id' => $rival->id]);
        Livewire::test(Lobby::class)->call('moveTo', $rooms[1]->id)->assertForbidden();
        $this->assertSame($rooms[0]->id, $rival->fresh()->current_room_id);
    }

    public function test_a_session_cannot_move_another_player(): void
    {
        [$game, $player, $companion, $rival, $rooms] = $this->expedition();
        $this->expectException(HttpException::class);
        app(GameService::class)->moveParticipant($companion, $rooms[0]->id);
    }

    public function test_answers_require_being_in_the_unsolved_room(): void
    {
        [$game, $player, $companion, $rival, $rooms] = $this->expedition();
        app(GameService::class)->submitAnswer($player, $rooms[0]->id, $rooms[0]->solution);
        session(['participant_id' => $companion->id]);
        $this->expectException(HttpException::class);
        app(GameService::class)->submitAnswer($companion, $rooms[1]->id, $rooms[1]->solution);
    }

    public function test_games_started_before_positions_were_added_initialize_without_losing_progress(): void
    {
        [$game, $player, $companion, $rival, $rooms] = $this->expedition();
        app(GameService::class)->submitAnswer($player, $rooms[0]->id, $rooms[0]->solution);
        $player->fresh()->forceFill(['current_room_id' => null])->save();
        Livewire::test(Lobby::class)->assertSet('roomId', $rooms[1]->id);
        $this->assertSame($rooms[1]->id, $player->fresh()->current_room_id);
        $this->assertSame(100, $player->team->progress()->sum('awarded_points'));
    }

    public function test_the_host_map_requires_room_ownership(): void
    {
        [$game] = $this->expedition();
        session()->forget('room_owner_token');
        $this->expectException(AuthorizationException::class);
        app(DungeonMapService::class)->forHost($game);
    }
}
