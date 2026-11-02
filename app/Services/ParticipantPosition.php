<?php

namespace App\Services;

use App\Models\Participant;
use Illuminate\Support\Collection;

class ParticipantPosition
{
    public function ensure(Participant $participant, Collection $progress): ?int
    {
        $current = $progress->firstWhere('room_id', $participant->current_room_id);
        if ($current?->unlocked_at) {
            return $current->room_id;
        }
        $fallback = $progress->first(fn ($step) => $step->unlocked_at && ! $step->solved_at)
            ?? $progress->filter(fn ($step) => $step->unlocked_at)->last();
        if (! $fallback) {
            return null;
        }

        // Initialize old games lazily and avoid overwriting a concurrent move.
        Participant::whereKey($participant->id)->where('current_room_id', $participant->current_room_id)
            ->update(['current_room_id' => $fallback->room_id]);
        $participant->refresh();

        return $participant->current_room_id;
    }
}
