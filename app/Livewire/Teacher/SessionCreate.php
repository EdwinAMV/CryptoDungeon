<?php

namespace App\Livewire\Teacher;

use App\Services\GameService;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class SessionCreate extends Component
{
    public string $mode = 'teams';

    public string $difficulty = 'easy';

    public $durationMinutes = 30;

    public function save(GameService $games): void
    {
        abort_unless(Auth::check(), 403);
        $game = $games->createSession(Auth::user(), [
            'mode' => $this->mode,
            'difficulty' => $this->difficulty,
            'duration_minutes' => $this->durationMinutes,
        ]);

        $this->redirectRoute('teacher.sessions.lobby', ['gameSession' => $game->id]);
    }

    public function render()
    {
        abort_unless(Auth::check(), 403);

        return view('livewire.teacher.session-create');
    }
}
