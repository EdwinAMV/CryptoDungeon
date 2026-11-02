<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ParticipantController extends Controller
{
    public function leave(Request $request): RedirectResponse
    {
        $request->session()->forget('participant_id');
        $request->session()->regenerate();

        return redirect()->route('home');
    }
}
