<?php

namespace App\Http\Controllers;

use App\Models\Participant;
use App\Services\GameService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ParticipantController extends Controller
{
    public function leave(Request $request, GameService $games): RedirectResponse
    {
        if ($participant = Participant::find($request->session()->get('participant_id'))) {
            $games->leaveSession($participant);
        }
        $request->session()->forget('participant_id');
        $request->session()->regenerate();

        return redirect()->route('home');
    }
}
