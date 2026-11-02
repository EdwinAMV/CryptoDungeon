<?php

namespace App\Livewire\Host;

use App\Services\GameService;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

class SessionCreate extends Component
{
    public string $mode = 'teams';

    public string $difficulty = 'easy';

    public function save(GameService $games): void
    {
        $key = 'create-room:'.request()->ip();

        if (RateLimiter::tooManyAttempts($key, 20)) {
            throw ValidationException::withMessages(['mode' => 'Demasiadas salas creadas. Espera un minuto.']);
        }

        $game = $games->createSession([
            'mode' => $this->mode,
            'difficulty' => $this->difficulty,
        ]);

        RateLimiter::hit($key, 60);
        session()->regenerate();
        session()->flash('download_room_answers', $game->id);

        $this->redirectRoute('rooms.lobby', ['gameSession' => $game->id]);
    }

    public function render()
    {
        return view('livewire.host.session-create', [
            'puzzleCounts' => config('cryptodungeon.puzzles_by_difficulty'),
        ]);
    }
}
