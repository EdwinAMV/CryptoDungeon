<?php

namespace App\Services;

use App\Enums\GameStatus;
use App\Models\GameSession;
use App\Models\Participant;
use App\Models\Team;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class GameService
{
    public function createSession(array $input): GameSession
    {
        $data = Validator::make($input, [
            'mode' => ['required', Rule::in(['individual', 'teams'])],
            'difficulty' => ['required', Rule::in(['easy', 'normal'])],
            'duration_minutes' => ['required', 'integer', 'min:5', 'max:120'],
        ])->validate();

        // The unique index also protects against simultaneous code generation.
        for ($attempt = 0; $attempt < 10; $attempt++) {
            try {
                return GameSession::create([
                    ...$data,
                    'owner_token_hash' => app(RoomAccess::class)->ownerHash(create: true),
                    'code' => (string) random_int(100000, 999999),
                    'status' => GameStatus::Lobby,
                ]);
            } catch (UniqueConstraintViolationException $exception) {
                if ($attempt === 9) {
                    throw $exception;
                }
            }
        }

        throw new \LogicException('No se pudo crear la sesión.');
    }

    public function addTeam(GameSession $gameSession, string $name): Team
    {
        Gate::authorize('update', $gameSession);
        $name = trim($name);
        Validator::make(['teamName' => $name], [
            'teamName' => ['required', 'string', 'min:2', 'max:40'],
        ])->validate();

        try {
            return DB::transaction(function () use ($gameSession, $name) {
                $game = GameSession::query()->lockForUpdate()->findOrFail($gameSession->id);
                $this->ensureLobby($game);

                if ($game->mode !== 'teams') {
                    throw ValidationException::withMessages(['teamName' => 'En modo individual los equipos se crean al entrar.']);
                }

                if ($game->teams()->count() >= 30) {
                    throw ValidationException::withMessages(['teamName' => 'Se ha alcanzado el máximo de 30 equipos.']);
                }

                return $game->teams()->create(['name' => $name]);
            }, attempts: 3);
        } catch (UniqueConstraintViolationException) {
            throw ValidationException::withMessages(['teamName' => 'Ya existe un equipo con ese nombre.']);
        }
    }

    public function joinSession(string $code, string $name, ?int $teamId): Participant
    {
        $data = Validator::make([
            'code' => trim($code),
            'displayName' => trim($name),
        ], [
            'code' => ['required', 'regex:/^[0-9]{6}$/'],
            'displayName' => ['required', 'string', 'min:2', 'max:40'],
        ])->validate();

        try {
            return DB::transaction(function () use ($data, $teamId) {
                $game = GameSession::query()->where('code', $data['code'])->lockForUpdate()->first();

                if (! $game) {
                    throw ValidationException::withMessages(['code' => 'No encontramos una sesión con ese código.']);
                }

                $this->ensureLobby($game);

                if ($game->participants()->count() >= 100) {
                    throw ValidationException::withMessages(['code' => 'La sesión está completa (100 participantes).']);
                }

                if ($game->mode === 'individual') {
                    $team = $game->teams()->create(['name' => $data['displayName']]);
                } else {
                    $team = $game->teams()->whereKey($teamId)->first();

                    if (! $team) {
                        throw ValidationException::withMessages(['teamId' => 'Selecciona un equipo de esta sesión.']);
                    }
                }

                return $team->participants()->create([
                    'display_name' => $data['displayName'],
                    'last_seen_at' => now(),
                ]);
            }, attempts: 3);
        } catch (UniqueConstraintViolationException) {
            throw ValidationException::withMessages(['displayName' => 'Ese nombre ya está en uso. Elige otro.']);
        }
    }

    private function ensureLobby(GameSession $game): void
    {
        if ($game->status !== GameStatus::Lobby) {
            throw ValidationException::withMessages(['code' => 'La partida ya no admite incorporaciones.']);
        }
    }
}
