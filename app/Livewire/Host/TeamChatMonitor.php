<?php

namespace App\Livewire\Host;

use App\Models\GameSession;
use App\Services\TeamChatService;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Locked;
use Livewire\Component;

class TeamChatMonitor extends Component
{
    #[Locked]
    public int $gameSessionId;

    #[Locked]
    public ?int $teamId = null;

    public function mount(int $gameSessionId): void
    {
        $this->gameSessionId = $gameSessionId;
        $this->game();
    }

    private function game(): GameSession
    {
        $game = GameSession::findOrFail($this->gameSessionId);
        Gate::authorize('view', $game);
        abort_unless($game->mode === 'teams', 403);

        return $game;
    }

    public function selectTeam(int $teamId): void
    {
        $game = $this->game();
        abort_unless($game->teams()->whereKey($teamId)->exists(), 403);
        $this->teamId = $teamId;
    }

    public function render()
    {
        $game = $this->game();
        $teams = $game->teams()->orderBy('id')->get(['id', 'name']);
        $this->teamId ??= $teams->first()?->id;

        return view('livewire.host.team-chat-monitor', [
            'teams' => $teams,
            'messages' => $this->teamId ? app(TeamChatService::class)->forHost($game, $this->teamId) : collect(),
        ]);
    }
}
