<?php

namespace App\Livewire\Player;

use App\Models\Participant;
use Livewire\Component;

class Lobby extends Component
{
    public function render()
    {
        // Identity comes only from the browser session, never a client-supplied ID.
        $participant = Participant::with('team.gameSession')->find(session('participant_id'));
        abort_unless($participant, 403);
        $participant->update(['last_seen_at' => now()]);

        return view('livewire.player.lobby', [
            'participant' => $participant,
            'game' => $participant->team->gameSession,
            'teammates' => $participant->team->participants()->orderBy('id')->get(),
        ]);
    }
}
