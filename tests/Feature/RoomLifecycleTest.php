<?php

namespace Tests\Feature;

use App\Enums\CipherType;
use App\Enums\GameStatus;
use App\Livewire\Host\SessionCreate;
use App\Livewire\Host\SessionLobby;
use App\Livewire\Player\Lobby;
use App\Models\GameSession;
use App\Services\DungeonMapService;
use App\Services\GameService;
use App\Services\PuzzleGenerator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class RoomLifecycleTest extends TestCase
{
    use RefreshDatabase;

    private function game(): array
    {
        $games = app(GameService::class);
        $game = $games->createSession(['mode' => 'individual', 'difficulty' => 'easy']);
        $first = $games->joinSession($game->code, 'Ana', null);
        $second = $games->joinSession($game->code, 'Luis', null);

        return [$game, $first, $second];
    }

    public function test_creation_prepares_a_single_dungeon_and_requests_a_host_only_download(): void
    {
        Livewire::test(SessionCreate::class)->set('mode', 'individual')->call('save')->assertHasNoErrors();
        $game = GameSession::sole();
        $this->assertCount(5, $game->rooms);
        $this->assertDatabaseCount('team_room_progress', 0);
        $lobby = Livewire::test(SessionLobby::class, ['gameSession' => $game])->assertSet('downloadAnswers', true);
        foreach ($game->rooms as $room) {
            $lobby->assertDontSee($room->solution);
        }
        Livewire::test(SessionLobby::class, ['gameSession' => $game])->assertSet('downloadAnswers', false);
        $response = $this->get(route('rooms.answers', $game))->assertOk()
            ->assertDownload("cryptodungeon-{$game->code}-respuestas.txt");
        $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control'));
        $contents = $response->streamedContent();
        foreach ($game->rooms as $room) {
            $this->assertStringContainsString($room->solution, $contents);
            $this->assertStringContainsString($room->ciphertext, $contents);
            $this->assertStringContainsString($room->cipher_type->label(), $contents);
        }
        $this->assertSame(5, substr_count($contents, 'Procedimiento de descifrado:'));
        $this->assertStringContainsString('desplazamiento =', $contents);
        $this->assertStringContainsString('Original: ABCDEFGHIJKLMNOPQRSTUVWXYZ', $contents);
        $this->assertStringContainsString('Parámetro: clave =', $contents);
        $ids = $game->rooms->modelKeys();
        app(GameService::class)->joinSession($game->code, 'Ana', null);
        app(GameService::class)->joinSession($game->code, 'Luis', null);
        app(GameService::class)->startSession($game);
        $this->assertSame($ids, $game->fresh()->rooms->modelKeys());
    }

    public function test_existing_dungeon_phrases_and_parameters_survive_the_new_bank_and_guide_download(): void
    {
        [$game, $player] = $this->game();
        $room = $game->rooms()->first();
        $legacyPhrase = 'LA LLAVE DE ORO ABRE LA PUERTA DEL ARCHIVO';
        $room->update(app(PuzzleGenerator::class)->generate(CipherType::Vigenere, $legacyPhrase));
        $snapshot = fn () => $game->rooms()->get()->map(fn ($saved) => [
            $saved->id, $saved->cipher_type, $saved->ciphertext, $saved->public_parameters, $saved->solution,
        ])->all();
        $before = $snapshot();
        $download = $this->get(route('rooms.answers', $game))->assertOk()->streamedContent();
        $this->assertStringContainsString($legacyPhrase, $download);
        $this->assertStringContainsString('Claro:   '.$legacyPhrase, $download);
        app(GameService::class)->startSession($game);
        $this->assertSame($before, $snapshot());
        session(['participant_id' => $player->id]);
        Livewire::test(Lobby::class)->assertSee($room->ciphertext)->assertDontSee($legacyPhrase);
    }

    public function test_players_and_other_browsers_cannot_download_answers_or_delete_a_room(): void
    {
        [$game, $player] = $this->game();
        session()->forget('room_owner_token');
        $this->withSession(['participant_id' => $player->id]);
        $this->get(route('rooms.answers', $game))->assertForbidden();
        $this->delete(route('rooms.destroy', $game))->assertForbidden();
        $this->assertDatabaseHas('game_sessions', ['id' => $game->id]);
        session()->forget('participant_id');
        $this->get(route('rooms.answers', $game))->assertForbidden();
        $this->delete(route('rooms.destroy', $game))->assertForbidden();
    }

    public function test_host_can_delete_a_playing_room_and_all_related_data_but_not_other_rooms(): void
    {
        [$game, $first] = $this->game();
        app(GameService::class)->startSession($game);
        session(['participant_id' => $first->id]);
        $room = $game->rooms()->first();
        app(GameService::class)->submitAnswer($first, $room->id, $room->solution);
        $component = Livewire::test(Lobby::class);
        $other = app(GameService::class)->createSession(['mode' => 'individual', 'difficulty' => 'normal']);
        $this->delete(route('rooms.destroy', $game))->assertRedirect(route('rooms.index'));
        $this->assertDatabaseMissing('game_sessions', ['id' => $game->id]);
        foreach (['teams', 'participants', 'team_room_progress', 'answer_attempts'] as $table) {
            $this->assertDatabaseCount($table, 0);
        }
        $this->assertDatabaseCount('rooms', 10);
        $this->assertDatabaseHas('game_sessions', ['id' => $other->id]);
        $component->call('$refresh')->assertSee('La sala ya no está disponible');
        $this->assertNull(session('participant_id'));
        $this->get(route('rooms.answers', $game))->assertNotFound();
    }

    public function test_leaving_the_lobby_frees_an_individual_place_and_allows_the_same_name_again(): void
    {
        [$game, $first] = $this->game();
        $this->withSession(['participant_id' => $first->id])->post(route('player.leave'))
            ->assertRedirect(route('home'))->assertSessionMissing('participant_id');
        $this->assertDatabaseMissing('participants', ['id' => $first->id]);
        $this->assertDatabaseMissing('teams', ['id' => $first->team_id]);
        app(GameService::class)->joinSession($game->code, 'Ana', null);
        $this->assertSame(2, $game->participants()->count());
    }

    public function test_departure_preserves_score_and_attempts_and_removes_the_marker(): void
    {
        [$game, $first] = $this->game();
        app(GameService::class)->startSession($game);
        session(['participant_id' => $first->id]);
        $room = $game->rooms()->first();
        app(GameService::class)->submitAnswer($first, $room->id, $room->solution);
        $this->post(route('player.leave'))->assertSessionMissing('participant_id');
        $this->assertNotNull($first->fresh()->left_at);
        $this->assertNull($first->fresh()->last_seen_at);
        $this->assertDatabaseCount('answer_attempts', 1);
        $this->assertSame(100, $first->team->progress()->sum('awarded_points'));
        $map = app(DungeonMapService::class)->forHost($game->fresh());
        $markers = array_merge(...array_column($map['rooms'], 'players'));
        $this->assertNotContains($first->id, array_column($markers, 'id'));
        $this->assertSame(GameStatus::Active, $game->fresh()->status);
    }

    public function test_completed_players_can_leave_without_waiting_for_the_other_team(): void
    {
        [$game, $first, $second] = $this->game();
        app(GameService::class)->startSession($game);
        session(['participant_id' => $first->id]);
        foreach ($game->rooms as $room) {
            app(GameService::class)->submitAnswer($first, $room->id, $room->solution);
        }
        Livewire::test(Lobby::class)->assertSee('Salir de la sala')->assertDontSee('Activar ambiente')
            ->assertDontSee('Descargar respuestas');
        $this->post(route('player.leave'))->assertRedirect(route('home'));
        $this->assertSame(500, $first->team->progress()->sum('awarded_points'));
        $this->assertSame(GameStatus::Active, $game->fresh()->status);
        // An abandoned final team must not keep the game active forever.
        $this->withSession(['participant_id' => $second->id])->post(route('player.leave'));
        $this->assertSame(GameStatus::Finished, $game->fresh()->status);
    }

    public function test_leaving_a_team_does_not_interrupt_remaining_members(): void
    {
        $games = app(GameService::class);
        $game = $games->createSession(['mode' => 'teams', 'difficulty' => 'easy']);
        $blue = $games->addTeam($game, 'Azul');
        $red = $games->addTeam($game, 'Rojo');
        $first = $games->joinSession($game->code, 'Ana', $blue->id);
        $remaining = $games->joinSession($game->code, 'Luis', $blue->id);
        $games->joinSession($game->code, 'Eva', $red->id);
        $games->startSession($game);
        $this->withSession(['participant_id' => $first->id])->post(route('player.leave'));
        $this->assertDatabaseHas('teams', ['id' => $blue->id]);
        session(['participant_id' => $remaining->id]);
        $room = $game->rooms()->first();
        $this->assertSame('correct', $games->submitAnswer($remaining, $room->id, $room->solution));
        $this->assertSame(GameStatus::Active, $game->fresh()->status);
        session(['participant_id' => $first->id]);
        $this->expectException(HttpException::class);
        $games->moveParticipant($first->fresh(), $room->id);
    }

    public function test_old_lobbies_can_download_answers_and_keep_the_same_puzzles_when_started(): void
    {
        [$game] = $this->game();
        $game->rooms()->delete();
        $this->get(route('rooms.answers', $game))->assertOk();
        $ids = $game->rooms()->pluck('id')->all();
        $this->assertCount(5, $ids);
        app(GameService::class)->startSession($game);
        $this->assertSame($ids, $game->fresh()->rooms->modelKeys());
    }
}
