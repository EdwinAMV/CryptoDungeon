<?php

use App\Http\Controllers\ParticipantController;
use App\Livewire\Host\SessionCreate;
use App\Livewire\Host\SessionLobby;
use App\Livewire\Player\JoinSession;
use App\Livewire\Player\Lobby;
use App\Models\GameSession;
use App\Services\RoomAccess;
use Illuminate\Support\Facades\Route;

Route::view('/', 'home')->name('home');

Route::post('/salir', [ParticipantController::class, 'leave'])->name('player.leave');

Route::prefix('salas')->name('rooms.')->group(function () {
    Route::get('/', function (RoomAccess $access) {
        return view('host.rooms', [
            'sessions' => GameSession::whereNotNull('owner_token_hash')
                ->where('owner_token_hash', $access->ownerHash())
                ->withCount('participants')->latest()->paginate(12),
        ]);
    })->name('index');
    Route::get('/crear', SessionCreate::class)->name('create');
    Route::get('/{gameSession}/lobby', SessionLobby::class)->name('lobby');
});

Route::get('/entrar', JoinSession::class)->name('player.join');
Route::get('/lobby', Lobby::class)->name('player.lobby');
