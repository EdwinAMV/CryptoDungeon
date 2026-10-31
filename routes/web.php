<?php

use App\Http\Controllers\AuthController;
use App\Livewire\Player\JoinSession;
use App\Livewire\Player\Lobby;
use App\Livewire\Teacher\SessionCreate;
use App\Livewire\Teacher\SessionLobby;
use App\Models\GameSession;
use Illuminate\Support\Facades\Route;

Route::view('/', 'home')->name('home');

Route::middleware('guest')->group(function () {
    Route::view('/profesor/acceder', 'auth.login')->name('login');
    Route::post('/profesor/acceder', [AuthController::class, 'login'])->middleware('throttle:30,1');
    Route::view('/profesor/registro', 'auth.register')->name('register');
    Route::post('/profesor/registro', [AuthController::class, 'register'])->middleware('throttle:5,1');
});

Route::post('/salir', [AuthController::class, 'logout'])->name('logout');

Route::middleware('auth')->prefix('profesor')->name('teacher.')->group(function () {
    Route::get('/sesiones', function () {
        return view('teacher.sessions', [
            'sessions' => GameSession::where('teacher_id', auth()->id())
                ->withCount('participants')->latest()->paginate(12),
        ]);
    })->name('sessions.index');
    Route::get('/sesiones/crear', SessionCreate::class)->name('sessions.create');
    Route::get('/sesiones/{gameSession}/lobby', SessionLobby::class)->name('sessions.lobby');
});

Route::get('/entrar', JoinSession::class)->name('player.join');
Route::get('/lobby', Lobby::class)->name('player.lobby');
