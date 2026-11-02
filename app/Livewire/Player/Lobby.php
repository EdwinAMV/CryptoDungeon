<?php

namespace App\Livewire\Player;

use App\Enums\GameStatus;
use App\Models\Participant;
use App\Services\GameService;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Locked;
use Livewire\Component;

class Lobby extends Component
{
    public string $answer = '';

    public string $feedback = '';

    #[Locked]
    public ?int $roomId = null;

    private function participant(): Participant
    {
        $participant = Participant::with('team.gameSession')->find(session('participant_id'));
        abort_unless($participant, 403);

        return $participant;
    }

    public function submit(GameService $games): void
    {
        $participant = $this->participant();
        abort_unless($this->roomId, 403);
        $key = 'answer:'.$participant->id;
        if (RateLimiter::tooManyAttempts($key, 30)) {
            throw ValidationException::withMessages(['answer' => 'Espera un momento antes de volver a intentarlo.']);
        }
        RateLimiter::hit($key, 60);
        $result = $games->submitAnswer($participant, $this->roomId, $this->answer);
        if ($result === 'incorrect') {
            $this->addError('answer', 'El mensaje no coincide todavía. Revisa el descifrado.');

            return;
        }
        $this->answer = '';
        $this->resetValidation();
        $this->feedback = match ($result) {
            'correct' => '¡Mensaje descifrado! Habitación completada.',
            'already_solved' => 'Tu equipo ya había completado esta habitación.',
            default => 'La partida ha finalizado.',
        };
    }

    public function render()
    {
        // Identity comes only from the browser session, never a client-supplied ID.
        $participant = $this->participant();
        $participant->update(['last_seen_at' => now()]);
        $game = $participant->team->gameSession;

        if ($game->status !== GameStatus::Lobby) {
            $progress = $participant->team->progress()->with('room')->get();
            $current = $progress->first(fn ($step) => $step->unlocked_at && ! $step->solved_at);
            if ($this->roomId !== $current?->room_id) {
                $this->roomId = $current?->room_id;
                $this->answer = '';
                $this->resetValidation();
            }

            return view('livewire.player.dungeon', [
                'participant' => $participant,
                'game' => $game,
                'puzzle' => $current?->room->publicPuzzle(),
                'tiles' => $progress->map(fn ($step) => [
                    'position' => $step->room->position, 'x' => $step->room->grid_x, 'y' => $step->room->grid_y,
                    'state' => $step->solved_at ? 'solved' : ($step->unlocked_at ? 'current' : 'locked'),
                ])->all(),
                'solvedCount' => $progress->whereNotNull('solved_at')->count(),
                'score' => $progress->sum('awarded_points'),
                'standings' => $game->teams()->withSum('progress as score', 'awarded_points')
                    ->withCount(['progress as solved_count' => fn ($query) => $query->whereNotNull('solved_at')])
                    ->orderByDesc('score')->orderBy('id')->get(),
            ]);
        }

        return view('livewire.player.lobby', [
            'participant' => $participant,
            'game' => $game,
            'teammates' => $participant->team->participants()->orderBy('id')->get(),
        ]);
    }
}
