<?php

namespace App\Livewire\Host;

use App\Enums\GameStatus;
use App\Models\GameSession;
use App\Services\DungeonMapService;
use App\Services\GameService;
use App\Services\PuzzleGenerator;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Locked;
use Livewire\Component;

class SessionLobby extends Component
{
    #[Locked]
    public int $gameSessionId;

    public string $teamName = '';

    public function mount(GameSession $gameSession): void
    {
        Gate::authorize('view', $gameSession);
        $this->gameSessionId = $gameSession->id;
    }

    public function addTeam(GameService $games): void
    {
        $games->addTeam($this->game(), $this->teamName);
        $this->reset('teamName');
    }

    public function start(GameService $games): void
    {
        $games->startSession($this->game());
        $this->resetValidation();
    }

    private function game(): GameSession
    {
        $game = GameSession::findOrFail($this->gameSessionId);
        Gate::authorize('view', $game);

        return $game;
    }

    public function render()
    {
        $game = $this->game()->load(['teams' => fn ($query) => $query
            ->with('participants')
            ->withSum('progress as score', 'awarded_points')
            ->withCount(['progress as solved_count' => fn ($progress) => $progress->whereNotNull('solved_at')]),
        ])->loadCount('rooms');

        return view('livewire.host.session-lobby', [
            'game' => $game,
            'startBlockReason' => app(GameService::class)->startBlockReason($game),
            'puzzleCount' => app(PuzzleGenerator::class)->countForDifficulty($game->difficulty),
            'map' => $game->status !== GameStatus::Lobby ? app(DungeonMapService::class)->forHost($game) : null,
        ]);
    }
}
