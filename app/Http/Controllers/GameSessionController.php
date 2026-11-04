<?php

namespace App\Http\Controllers;

use App\Models\GameSession;
use App\Services\GameService;
use App\Services\PuzzleSolutionGuide;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\StreamedResponse;

class GameSessionController extends Controller
{
    public function destroy(GameSession $gameSession, GameService $games): RedirectResponse
    {
        $games->deleteSession($gameSession);

        return redirect()->route('rooms.index')->with('status', 'Sala eliminada.');
    }

    public function answers(GameSession $gameSession, GameService $games, PuzzleSolutionGuide $guide): StreamedResponse
    {
        Gate::authorize('view', $gameSession);
        // Also supports lobbies created before puzzles were generated on creation.
        $games->prepareDungeon($gameSession);

        return response()->streamDownload(function () use ($gameSession, $guide) {
            echo "CryptoDungeon — Respuestas de la sala {$gameSession->code}\nSolo para el anfitrión\n\n";
            echo "Alfabeto A-Z, sin Ñ ni tildes. Los espacios se conservan al cifrar.\n";
            echo "Al validar se ignoran mayúsculas, tildes, espacios y puntuación.\n\n";
            foreach ($gameSession->rooms()->get() as $room) {
                echo "Habitación {$room->position} — {$room->cipher_type->label()}\n";
                echo "Mensaje cifrado: {$room->ciphertext}\nRespuesta: {$room->solution}\n";
                echo $guide->describe($room)."\n";
            }
        }, "cryptodungeon-{$gameSession->code}-respuestas.txt", [
            'Content-Type' => 'text/plain; charset=UTF-8',
            'Cache-Control' => 'private, no-store, max-age=0',
            'X-Content-Type-Options' => 'nosniff',
            'X-Robots-Tag' => 'noindex, nofollow',
        ]);
    }
}
