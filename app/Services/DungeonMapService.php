<?php

namespace App\Services;

use App\Models\GameSession;
use App\Models\Participant;
use App\Models\TeamRoomProgress;
use Illuminate\Support\Facades\Gate;

class DungeonMapService
{
    public function forHost(GameSession $game): array
    {
        Gate::authorize('view', $game);
        $game->loadMissing('teams.participants');
        $rooms = $game->rooms()->get(['id', 'position', 'grid_x', 'grid_y']);
        $progress = TeamRoomProgress::whereIn('team_id', $game->teams->modelKeys())->orderBy('id')->get();
        $tiles = $rooms->map(fn ($room) => [
            'id' => $room->id, 'position' => $room->position, 'x' => $room->grid_x, 'y' => $room->grid_y,
            'state' => 'observed',
        ])->all();
        $players = [];
        $teams = [];
        foreach ($game->teams as $team) {
            $color = $this->teamColor($team->id);
            $teams[] = ['name' => $team->name, 'color' => $color];
            $steps = $progress->where('team_id', $team->id);
            $fallback = $steps->first(fn ($step) => $step->unlocked_at && ! $step->solved_at)
                ?? $steps->filter(fn ($step) => $step->unlocked_at)->last();
            foreach ($team->participants->whereNull('left_at') as $player) {
                $location = $rooms->contains('id', $player->current_room_id) ? $player->current_room_id : $fallback?->room_id;
                $players[] = $this->marker($player, $team->name, $color, $location);
            }
        }

        return app(DungeonMapPresenter::class)->present($tiles, $players, $teams);
    }

    public function forPlayer(Participant $participant, array $tiles): array
    {
        abort_unless((int) session('participant_id') === $participant->id && ! $participant->left_at, 403);
        $team = $participant->team;
        $color = $this->teamColor($team->id);
        $steps = $team->progress()->orderBy('id')->get();
        $fallback = $steps->first(fn ($step) => $step->unlocked_at && ! $step->solved_at)
            ?? $steps->filter(fn ($step) => $step->unlocked_at)->last();
        $players = $team->participants()->whereNull('left_at')->get()->map(fn ($player) => $this->marker(
            $player, $team->name, $color, $player->current_room_id ?? $fallback?->room_id,
            $player->id === $participant->id,
        ))->all();

        return app(DungeonMapPresenter::class)->present($tiles, $players, [['name' => $team->name, 'color' => $color]], interactive: true);
    }

    private function marker(Participant $player, string $teamName, string $color, ?int $roomId, bool $self = false): array
    {
        return [
            'id' => $player->id, 'name' => $player->display_name, 'team_name' => $teamName,
            'room_id' => $roomId, 'color' => $player->displayColor(), 'team_color' => $color, 'self' => $self,
            'online' => (bool) $player->last_seen_at?->gte(now()->subSeconds(config('cryptodungeon.presence_seconds'))),
        ];
    }

    private function teamColor(int $id): string
    {
        return 'hsl('.(($id * 137) % 360).' 65% 68%)';
    }
}
