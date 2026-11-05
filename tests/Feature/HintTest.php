<?php

namespace Tests\Feature;

use App\Enums\CipherType;
use App\Enums\GameStatus;
use App\Livewire\Player\Lobby;
use App\Services\GameService;
use App\Services\HintService;
use App\Services\PuzzleGenerator;
use App\Services\PuzzleHintGenerator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class HintTest extends TestCase
{
    use RefreshDatabase;

    private function game(): array
    {
        $this->freezeTime();
        $games = app(GameService::class);
        $game = $games->createSession(['mode' => 'teams', 'difficulty' => 'easy']);
        $blue = $games->addTeam($game, 'Azul');
        $red = $games->addTeam($game, 'Rojo');
        $player = $games->joinSession($game->code, 'Ana', $blue->id);
        $companion = $games->joinSession($game->code, 'Eva', $blue->id);
        $rival = $games->joinSession($game->code, 'Luis', $red->id);
        $game->rooms()->first()->update((new PuzzleGenerator)->generate(CipherType::Caesar, 'LA RAM ES VOLATIL'));
        $games->startSession($game);
        session(['participant_id' => $player->id]);

        return [$game, $player->fresh(), $companion->fresh(), $rival->fresh(), $game->rooms()->get()];
    }

    public function test_no_unpurchased_letter_is_decoded_even_after_the_wait(): void
    {
        [$game, $player, $companion, $rival, $rooms] = $this->game();
        $this->mock(PuzzleHintGenerator::class)->shouldNotReceive('letterAt');
        $component = Livewire::test(Lobby::class)->set('answer', 'BORRADOR');
        $this->travel(89)->seconds();
        $component->call('requestHint', $rooms[0]->id, 1, 1)->assertHasErrors(['hint'])->assertSet('answer', 'BORRADOR')
            ->assertViewHas('hints', fn ($state) => $state['remaining'] === 1 && $state['words'][0][1]['plain'] === null);
        $this->travel(1)->seconds();
        $component->call('$refresh')->assertViewHas('hints', fn ($state) => $state['available'] && $state['words'][0][1]['plain'] === null);
        $this->assertDatabaseCount('hint_usages', 0);
    }

    public function test_only_the_selected_occurrence_is_revealed_and_the_team_pays_once(): void
    {
        [$game, $player, $companion, $rival, $rooms] = $this->game();
        $this->travel(90)->seconds();
        Livewire::test(Lobby::class)->set('answer', 'BORRADOR')->call('requestHint', $rooms[0]->id, 1, 1)
            ->assertHasNoErrors()->assertSet('answer', 'BORRADOR')->assertViewHas('availablePoints', 90)
            ->assertViewHas('hints', fn ($state) => $state['words'][0][1]['plain'] === 'A'
                && $state['words'][1][1]['plain'] === null && $state['revealed_count'] === 1
                && $state['next_penalty'] === 20 && $state['remaining'] === 30);
        session(['participant_id' => $companion->id]);
        Livewire::test(Lobby::class)->assertViewHas('roomHintPenalty', 10)
            ->call('requestHint', $rooms[0]->id, 1, 1)->assertHasNoErrors();
        $this->assertDatabaseCount('hint_usages', 1);
        $this->assertDatabaseHas('hint_usages', ['participant_id' => $player->id, 'character_position' => 1, 'penalty' => 10]);
        $this->assertSame(0, $companion->hints()->count());
        session(['participant_id' => $rival->id]);
        Livewire::test(Lobby::class)->assertViewHas('roomHintPenalty', 0)
            ->assertViewHas('hints', fn ($state) => $state['revealed_count'] === 0 && $state['words'][0][1]['plain'] === null);
    }

    public function test_waits_and_prices_grow_and_old_quotes_cannot_charge_a_higher_price(): void
    {
        [$game, $player, $companion, $rival, $rooms] = $this->game();
        $this->travel(90)->seconds();
        $component = Livewire::test(Lobby::class)->call('requestHint', $rooms[0]->id, 0, 1)->assertHasNoErrors();
        $component->call('requestHint', $rooms[0]->id, 3, 2)->assertHasErrors(['hint']);
        $this->travel(30)->seconds();
        $component->call('requestHint', $rooms[0]->id, 3, 1)->assertHasErrors(['hint']);
        $this->assertDatabaseCount('hint_usages', 1);
        $component->call('requestHint', $rooms[0]->id, 3, 2)->assertHasNoErrors()->assertViewHas('availablePoints', 70);
        $this->travel(30)->seconds();
        $component->call('requestHint', $rooms[0]->id, 4, 3)->assertHasNoErrors()->assertViewHas('availablePoints', 40);
        $this->assertSame([10, 20, 30], $player->hints()->orderBy('level')->pluck('penalty')->all());
        $this->assertDatabaseCount('answer_attempts', 0);
    }

    public function test_the_cost_and_letters_survive_reload_and_legacy_purchases(): void
    {
        [$game, $player, $companion, $rival, $rooms] = $this->game();
        $progress = $player->team->progress()->where('room_id', $rooms[0]->id)->firstOrFail();
        foreach ([1 => 10, 2 => 20] as $level => $penalty) {
            $progress->hints()->create(['participant_id' => $player->id, 'level' => $level, 'penalty' => $penalty]);
        }
        $this->travel(180)->seconds();
        Livewire::test(Lobby::class)->assertViewHas('roomHintPenalty', 30)
            ->assertViewHas('hints', fn ($state) => $state['revealed_count'] === 6 && $state['next_number'] === 3 && $state['words'][1][0]['plain'] === 'R')
            ->call('requestHint', $rooms[0]->id, 0, 1)->assertHasNoErrors();
        $this->assertDatabaseCount('hint_usages', 2);
        Livewire::test(Lobby::class)->call('requestHint', $rooms[0]->id, 8, 3)->assertHasNoErrors()->assertViewHas('roomHintPenalty', 60);
        config(['cryptodungeon.hints.penalty_increment' => 40]);
        Livewire::test(Lobby::class)->assertViewHas('roomHintPenalty', 60);
    }

    public function test_letter_costs_reduce_only_their_room_and_keep_negative_scores(): void
    {
        [$game, $player, $companion, $rival, $rooms] = $this->game();
        $games = app(GameService::class);
        $this->travel(120)->seconds();
        app(HintService::class)->request($player, $rooms[0]->id, 0, 1);
        app(HintService::class)->request($player, $rooms[0]->id, 3, 2);
        for ($index = 0; $index < 8; $index++) {
            $games->submitAnswer($player, $rooms[0]->id, 'INCORRECTO');
        }
        $games->submitAnswer($player, $rooms[0]->id, $rooms[0]->solution);
        $this->assertSame(-10, $player->team->progress()->sum('awarded_points'));
        Livewire::test(Lobby::class)->assertViewHas('availablePoints', 100)
            ->assertViewHas('hints', fn ($state) => $state['remaining'] === 90 && $state['revealed_count'] === 0);
        foreach ($rooms->slice(1) as $room) {
            $games->submitAnswer($player, $room->id, $room->solution);
        }
        Livewire::test(Lobby::class)->assertViewHas('hintCount', 2)->assertViewHas('hintPenalty', 30)->assertViewHas('score', 390);
        $games->deleteSession($game);
        $this->assertDatabaseCount('hint_usages', 0);
    }

    public function test_spaces_invalid_indices_and_fake_prices_are_rejected(): void
    {
        [$game, $player, $companion, $rival, $rooms] = $this->game();
        $this->travel(180)->seconds();
        foreach ([-1, 2, 1000] as $position) {
            Livewire::test(Lobby::class)->call('requestHint', $rooms[0]->id, $position, 1)->assertHasErrors(['hint']);
        }
        Livewire::test(Lobby::class)->call('requestHint', $rooms[0]->id, 1, 999)->assertHasErrors(['hint']);
        $this->assertDatabaseCount('hint_usages', 0);
    }

    public function test_other_identities_and_stale_room_modals_are_rejected(): void
    {
        [$game, $player, $companion, $rival, $rooms] = $this->game();
        $this->travel(180)->seconds();
        Livewire::test(Lobby::class)->call('requestHint', $rooms[1]->id, 0, 1)->assertForbidden();
        $this->expectException(HttpException::class);
        app(HintService::class)->request($rival, $rooms[0]->id, 0, 1);
    }

    public function test_locked_rooms_are_rejected_by_the_service(): void
    {
        [$game, $player, $companion, $rival, $rooms] = $this->game();
        $this->travel(180)->seconds();
        $this->expectException(HttpException::class);
        app(HintService::class)->request($player, $rooms[1]->id, 0, 1);
    }

    public function test_departed_players_are_rejected(): void
    {
        [$game, $player, $companion, $rival, $rooms] = $this->game();
        $this->travel(180)->seconds();
        app(GameService::class)->leaveSession($player);
        $this->expectException(HttpException::class);
        app(HintService::class)->request($player, $rooms[0]->id, 0, 1);
    }

    public function test_solved_rooms_and_finished_games_are_rejected(): void
    {
        [$game, $player, $companion, $rival, $rooms] = $this->game();
        $this->travel(180)->seconds();
        app(GameService::class)->submitAnswer($player, $rooms[0]->id, $rooms[0]->solution);
        Livewire::test(Lobby::class)->call('requestHint', $rooms[0]->id, 0, 1)->assertForbidden();
        $game->update(['status' => GameStatus::Finished]);
        $this->expectException(HttpException::class);
        app(HintService::class)->request($player, $rooms[1]->id, 0, 1);
    }

    public function test_all_positions_can_be_paid_for_in_individual_mode_without_auto_solving(): void
    {
        $this->freezeTime();
        $games = app(GameService::class);
        $game = $games->createSession(['mode' => 'individual', 'difficulty' => 'easy']);
        $player = $games->joinSession($game->code, 'Ana', null);
        $games->joinSession($game->code, 'Luis', null);
        $games->startSession($game);
        session(['participant_id' => $player->id]);
        $this->travel(1)->hours();
        $room = $game->rooms()->first();
        $number = 0;
        foreach (str_split($room->ciphertext) as $position => $letter) {
            if ($letter !== ' ') {
                app(HintService::class)->request($player, $room->id, $position, ++$number);
            }
        }
        Livewire::test(Lobby::class)->assertViewHas('hints', fn ($state) => $state['complete'] && ! $state['available']);
        $this->assertNull($player->team->progress()->where('room_id', $room->id)->first()->solved_at);
        $games->submitAnswer($player, $room->id, $room->solution);
        $this->assertSame(100 - 10 * $number * ($number + 1) / 2, $player->team->progress()->sum('awarded_points'));
    }
}
