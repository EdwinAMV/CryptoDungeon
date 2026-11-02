<?php

namespace App\Livewire\Player;

use App\Enums\GameStatus;
use App\Models\Participant;
use App\Services\DungeonMapService;
use App\Services\GameService;
use App\Services\ParticipantPosition;
use App\Services\ScoreCalculator;
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
        abort_unless($participant && ! $participant->left_at, 403);

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

    public function moveTo(int $roomId, GameService $games): void
    {
        $games->moveParticipant($this->participant(), $roomId);
        $this->answer = '';
        $this->feedback = '';
        $this->resetValidation();
    }

    public function render()
    {
        $identity = session('participant_id');
        if ($identity && ! Participant::whereKey($identity)->whereNull('left_at')->exists()) {
            session()->forget('participant_id');

            return view('livewire.player.room-closed');
        }
        // Identity comes only from the browser session, never a client-supplied ID.
        $participant = $this->participant();
        $participant->update(['last_seen_at' => now()]);
        $game = $participant->team->gameSession;

        if ($game->status !== GameStatus::Lobby) {
            $progress = $participant->team->progress()->with('room')->withCount([
                'attempts as attempt_count',
                'attempts as incorrect_count' => fn ($query) => $query->where('is_correct', false),
            ])->orderBy('id')->get();
            $location = app(ParticipantPosition::class)->ensure($participant, $progress);
            $selected = $progress->firstWhere('room_id', $location);
            $current = $selected && ! $selected->solved_at ? $selected : null;
            if ($this->roomId !== $current?->room_id) {
                $this->roomId = $current?->room_id;
                $this->answer = '';
                $this->resetValidation();
            }

            $tiles = $progress->map(fn ($step) => [
                'id' => $step->room_id, 'position' => $step->room->position,
                'x' => $step->room->grid_x, 'y' => $step->room->grid_y,
                'state' => $step->solved_at ? 'solved' : ($step->unlocked_at ? 'current' : 'locked'),
                'selected' => $step->room_id === $location,
                'can_move' => $step->unlocked_at && $selected && abs($step->room->position - $selected->room->position) === 1,
            ])->all();
            $next = $progress->first(fn ($step) => $step->unlocked_at && ! $step->solved_at);

            return view('livewire.player.dungeon', [
                'participant' => $participant,
                'game' => $game,
                'puzzle' => $current?->room->publicPuzzle(),
                'tiles' => $tiles,
                'map' => app(DungeonMapService::class)->forPlayer($participant, $tiles),
                'selectedPosition' => $selected?->room->position,
                'nextPosition' => $next?->room->position,
                'solvedCount' => $progress->whereNotNull('solved_at')->count(),
                'score' => $progress->sum('awarded_points'),
                'attemptCount' => $progress->sum('attempt_count'),
                'incorrectCount' => $progress->sum('incorrect_count'),
                'roomIncorrectCount' => $current?->incorrect_count ?? 0,
                'availablePoints' => app(ScoreCalculator::class)->solvedRoom($current?->incorrect_count ?? 0),
                'roomResults' => $progress->map(fn ($step) => [
                    'position' => $step->room->position, 'attempts' => $step->attempt_count,
                    'incorrect' => $step->incorrect_count, 'points' => $step->awarded_points,
                    'solved' => $step->solved_at !== null,
                ])->all(),
                'playerResults' => $participant->team->participants()->withCount([
                    'attempts as attempt_count',
                    'attempts as incorrect_count' => fn ($query) => $query->where('is_correct', false),
                ])->orderBy('id')->get(),
                'standings' => $game->teams()->withSum('progress as score', 'awarded_points')
                    ->withCount([
                        'progress as solved_count' => fn ($query) => $query->whereNotNull('solved_at'),
                        'attempts as attempt_count',
                        'attempts as incorrect_count' => fn ($query) => $query->where('is_correct', false),
                    ])
                    ->orderByDesc('score')->orderBy('id')->get(),
            ]);
        }

        return view('livewire.player.lobby', [
            'participant' => $participant,
            'game' => $game,
            'teammates' => $participant->team->participants()->whereNull('left_at')->orderBy('id')->get(),
        ]);
    }
}
