<?php

namespace Tests\Feature;

use App\Services\DungeonMapService;
use App\Services\GameService;
use App\Services\TeamChatService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ParticipantColorsTest extends TestCase
{
    use RefreshDatabase;

    public function test_colors_are_distinct_stable_and_consistent_between_map_and_chat(): void
    {
        $games = app(GameService::class);
        $game = $games->createSession(['mode' => 'teams', 'difficulty' => 'easy']);
        $team = $games->addTeam($game, 'Azul');
        $rival = $games->addTeam($game, 'Rojo');
        $players = [];
        for ($index = 0; $index < 10; $index++) {
            $players[] = $games->joinSession($game->code, 'Jugador '.$index, $team->id);
        }
        $games->joinSession($game->code, 'Rival', $rival->id);
        $hues = array_map(fn ($player) => $player->color_hue, $players);
        $this->assertCount(10, array_unique($hues));
        foreach ($players as $player) {
            $this->assertGreaterThanOrEqual(0, $player->color_hue);
            $this->assertLessThan(360, $player->color_hue);
            $this->assertSame($player->displayColor(), $player->fresh()->displayColor());
        }
        session(['participant_id' => $players[0]->id]);
        app(TeamChatService::class)->send('Hola');
        $games->startSession($game);
        $map = app(DungeonMapService::class)->forHost($game);
        $marker = collect(array_merge(...array_column($map['rooms'], 'players')))->firstWhere('id', $players[0]->id);
        $this->assertSame($players[0]->displayColor(), $marker['color']);
        $this->assertSame($players[0]->displayColor(), app(TeamChatService::class)->messages()->first()->participant->displayColor());
    }
}
