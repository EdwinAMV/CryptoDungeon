<?php

namespace App\Livewire\Player;

use App\Enums\GameStatus;
use App\Models\GameSession;
use App\Models\Participant;
use App\Services\GameService;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

class JoinSession extends Component
{
    public string $code = '';

    public string $displayName = '';

    public ?int $teamId = null;

    public function mount(): void
    {
        if (Participant::whereKey(session('participant_id'))->whereNull('left_at')->exists()) {
            $this->redirectRoute('player.lobby');
        }
    }

    public function updatedCode(): void
    {
        $this->teamId = null;
        $this->resetValidation();
    }

    public function join(GameService $games): void
    {
        if (Participant::whereKey(session('participant_id'))->whereNull('left_at')->exists()) {
            $this->redirectRoute('player.lobby');

            return;
        }

        // Allow classrooms sharing a single public IP address.
        $key = 'join:'.request()->ip();
        if (RateLimiter::tooManyAttempts($key, 150)) {
            throw ValidationException::withMessages(['code' => 'Demasiados intentos. Espera un minuto.']);
        }
        RateLimiter::hit($key, 60);

        $participant = $games->joinSession($this->code, $this->displayName, $this->teamId);
        session()->regenerate();
        session(['participant_id' => $participant->id]);
        $this->redirectRoute('player.lobby');
    }

    public function render()
    {
        $game = preg_match('/^[0-9]{6}$/', $this->code)
            ? GameSession::where('code', $this->code)->where('status', GameStatus::Lobby->value)->first()
            : null;

        return view('livewire.player.join-session', [
            'game' => $game,
            'teams' => $game?->mode === 'teams' ? $game->teams()->orderBy('name')->get(['id', 'name']) : collect(),
        ]);
    }
}
