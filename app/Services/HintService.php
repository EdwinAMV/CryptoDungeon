<?php

namespace App\Services;

use App\Enums\GameStatus;
use App\Models\GameSession;
use App\Models\Participant;
use App\Models\TeamRoomProgress;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class HintService
{
    public function request(Participant $participant, int $roomId, int $position, int $expectedNumber): void
    {
        abort_unless((int) session('participant_id') === $participant->id, 403);

        DB::transaction(function () use ($participant, $roomId, $position, $expectedNumber) {
            $player = Participant::with('team')->findOrFail($participant->id);
            $game = GameSession::query()->lockForUpdate()->findOrFail($player->team->game_session_id);
            $player = Participant::query()->lockForUpdate()->findOrFail($player->id);
            abort_unless(! $player->left_at && $game->status === GameStatus::Active, 403);
            $progress = TeamRoomProgress::where('team_id', $player->team_id)->where('room_id', $roomId)->lockForUpdate()->first();
            abort_unless($progress && $progress->unlocked_at && ! $progress->solved_at && $progress->room->game_session_id === $game->id, 403);
            $steps = $player->team->progress()->orderBy('id')->get();
            abort_unless(app(ParticipantPosition::class)->ensure($player, $steps) === $roomId, 403);
            $text = $progress->room->ciphertext;
            if ($position < 0 || $position >= strlen($text) || ! preg_match('/\A[A-Z]\z/', $text[$position])) {
                throw ValidationException::withMessages(['hint' => 'Selecciona una letra válida del mensaje.']);
            }
            $positions = $this->revealedPositions($progress);
            if (in_array($position, $positions, true)) {
                return;
            }
            $quote = $this->quote($progress);
            if ($quote['number'] !== $expectedNumber) {
                throw ValidationException::withMessages(['hint' => 'Tu equipo ha utilizado otro comodín. Selecciona de nuevo una letra para confirmar el coste actualizado.']);
            }
            if ($quote['remaining'] > 0) {
                throw ValidationException::withMessages(['hint' => 'Todavía no está disponible el siguiente comodín.']);
            }
            $progress->hints()->create([
                'participant_id' => $player->id, 'level' => $quote['number'],
                'penalty' => $quote['penalty'], 'character_position' => $position,
            ]);
        }, attempts: 3);
    }

    public function state(Participant $participant, TeamRoomProgress $progress): array
    {
        abort_unless((int) session('participant_id') === $participant->id && ! $participant->left_at
            && $participant->team_id === $progress->team_id && $participant->current_room_id === $progress->room_id
            && $participant->team->gameSession->status === GameStatus::Active && $progress->unlocked_at && ! $progress->solved_at
            && $progress->room->game_session_id === $participant->team->game_session_id, 403);
        $positions = $this->revealedPositions($progress);
        $words = [];
        $word = [];
        $selectable = 0;
        foreach (str_split($progress->room->ciphertext) as $index => $letter) {
            if ($letter === ' ') {
                if ($word !== []) {
                    $words[] = $word;
                    $word = [];
                }

                continue;
            }
            $isLetter = (bool) preg_match('/\A[A-Z]\z/', $letter);
            $revealed = $isLetter && in_array($index, $positions, true);
            $word[] = [
                'position' => $index, 'cipher' => $letter, 'revealed' => $revealed, 'is_letter' => $isLetter,
                // Never decode or serialize unpurchased letters, even for a closed modal.
                'plain' => $revealed ? app(PuzzleHintGenerator::class)->letterAt($progress->room, $index) : null,
            ];
            if ($isLetter && ! $revealed) {
                $selectable++;
            }
        }
        if ($word !== []) {
            $words[] = $word;
        }
        $quote = $this->quote($progress);

        return [
            'words' => $words, 'next_number' => $quote['number'], 'next_penalty' => $quote['penalty'],
            'remaining' => $quote['remaining'], 'available' => $quote['remaining'] === 0 && $selectable > 0,
            'complete' => $selectable === 0, 'revealed_count' => count($positions),
        ];
    }

    private function quote(TeamRoomProgress $progress): array
    {
        $number = (int) $progress->hints->max('level') + 1;
        $seconds = config('cryptodungeon.hints.initial_wait_seconds') + ($number - 1) * config('cryptodungeon.hints.wait_increment_seconds');

        return [
            'number' => $number, 'penalty' => $number * config('cryptodungeon.hints.penalty_increment'),
            'remaining' => max(0, $progress->unlocked_at->addSeconds($seconds)->getTimestamp() - now()->getTimestamp()),
        ];
    }

    private function revealedPositions(TeamRoomProgress $progress): array
    {
        $positions = [];
        foreach ($progress->hints as $usage) {
            if ($usage->character_position !== null) {
                $positions[] = $usage->character_position;
            } else {
                // Preserve the fragments already paid for under the former two-level system.
                array_push($positions, ...app(PuzzleHintGenerator::class)->legacyPositions($progress->room, $usage->level));
            }
        }

        return array_values(array_unique($positions));
    }
}
