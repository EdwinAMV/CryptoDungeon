<?php

namespace App\Livewire\Host;

use App\Models\GameSession;
use App\Services\GameService;
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

    private function game(): GameSession
    {
        $game = GameSession::findOrFail($this->gameSessionId);
        Gate::authorize('view', $game);

        return $game;
    }

    public function render()
    {
        $game = $this->game()->load(['teams.participants']);

        return view('livewire.host.session-lobby', ['game' => $game]);
    }
}
