<?php

namespace App\Services;

use App\Enums\CipherType;
use App\Enums\GameStatus;
use App\Models\GameSession;
use App\Models\Participant;
use App\Models\Team;
use App\Models\TeamRoomProgress;
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
            'difficulty' => ['required', Rule::in(['easy', 'normal', 'hard'])],
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

    public function startBlockReason(GameSession $game): ?string
    {
        $game->loadMissing('teams.participants');
        $cutoff = now()->subSeconds(config('cryptodungeon.presence_seconds'));
        $online = fn ($team) => $team->participants->filter(fn ($player) => $player->last_seen_at?->gte($cutoff));

        if ($game->mode === 'individual') {
            return $game->teams->sum(fn ($team) => $online($team)->count()) >= 2
                ? null : 'Se necesitan al menos 2 jugadores conectados para empezar.';
        }
        if ($game->teams->count() < 2) {
            return 'Crea al menos 2 equipos para empezar.';
        }
        if ($game->teams->contains(fn ($team) => $online($team)->isEmpty())) {
            return 'Cada equipo necesita al menos 1 jugador conectado para empezar.';
        }

        return null;
    }

    public function startSession(GameSession $gameSession): GameSession
    {
        Gate::authorize('update', $gameSession);

        return DB::transaction(function () use ($gameSession) {
            $game = GameSession::query()->lockForUpdate()->findOrFail($gameSession->id);
            if ($game->status === GameStatus::Active) {
                return $game;
            }
            $this->ensureLobby($game);
            if ($reason = $this->startBlockReason($game)) {
                throw ValidationException::withMessages(['start' => $reason]);
            }

            $generator = app(PuzzleGenerator::class);
            $count = $generator->countForDifficulty($game->difficulty);
            $phrases = app(PhraseGenerator::class)->generateMany($count);
            $map = app(MapGenerator::class)->generate($count);
            $ciphers = CipherType::cases();
            shuffle($ciphers);
            $roomIds = [];
            foreach ($map['rooms'] as $index => $coordinates) {
                $roomIds[] = $game->rooms()->create([
                    ...$coordinates,
                    ...$generator->generate($ciphers[$index % count($ciphers)], $phrases[$index]),
                ])->id;
            }

            $startedAt = now();
            $progress = [];
            foreach ($game->teams as $team) {
                foreach ($roomIds as $index => $roomId) {
                    $progress[] = [
                        'team_id' => $team->id, 'room_id' => $roomId,
                        'unlocked_at' => $index === 0 ? $startedAt : null,
                        'created_at' => $startedAt, 'updated_at' => $startedAt,
                    ];
                }
            }
            TeamRoomProgress::insert($progress);
            Participant::whereIn('team_id', $game->teams->modelKeys())->update(['current_room_id' => $roomIds[0]]);
            $game->forceFill([
                'status' => GameStatus::Active, 'started_at' => $startedAt, 'ends_at' => null,
                'map_layout' => ['columns' => $map['columns'], 'rows' => $map['rows']],
            ])->save();

            return $game;
        }, attempts: 3);
    }

    public function submitAnswer(Participant $participant, int $roomId, string $answer): string
    {
        abort_unless((int) session('participant_id') === $participant->id, 403);
        Validator::make(['answer' => $answer], ['answer' => ['required', 'string', 'max:300']])->validate();

        return DB::transaction(function () use ($participant, $roomId, $answer) {
            $player = Participant::findOrFail($participant->id);
            $gameId = $player->team->game_session_id;
            $game = GameSession::query()->lockForUpdate()->findOrFail($gameId);
            $player = Participant::query()->lockForUpdate()->findOrFail($player->id);
            $team = Team::query()->lockForUpdate()->findOrFail($player->team_id);
            if ($game->status === GameStatus::Finished) {
                return 'finished';
            }
            if ($game->status !== GameStatus::Active) {
                throw ValidationException::withMessages(['answer' => 'La partida todavía no ha empezado.']);
            }

            $progress = $team->progress()->where('room_id', $roomId)->lockForUpdate()->first();
            abort_unless($progress && $progress->unlocked_at && $progress->room->game_session_id === $game->id, 403);
            if ($progress->solved_at) {
                return 'already_solved';
            }

            $steps = $team->progress()->orderBy('id')->get();
            $location = app(ParticipantPosition::class)->ensure($player, $steps);
            abort_unless($location === $roomId, 403);

            $correct = app(AnswerValidator::class)->matches($answer, $progress->room->solution);
            DB::table('answer_attempts')->insert([
                'team_room_progress_id' => $progress->id, 'participant_id' => $player->id,
                'is_correct' => $correct, 'created_at' => now(), 'updated_at' => now(),
            ]);
            if (! $correct) {
                return 'incorrect';
            }

            $progress->update(['solved_at' => now(), 'awarded_points' => app(ScoreCalculator::class)->solvedRoom()]);
            $next = $game->rooms()->where('position', '>', $progress->room->position)->first();
            if ($next) {
                $team->progress()->where('room_id', $next->id)->update(['unlocked_at' => now()]);
                $player->forceFill(['current_room_id' => $next->id])->save();
            } else {
                $team->forceFill(['finished_at' => now()])->save();
                if (! $game->teams()->whereNull('finished_at')->exists()) {
                    $game->forceFill(['status' => GameStatus::Finished, 'finished_at' => now()])->save();
                }
            }

            return 'correct';
        }, attempts: 3);
    }

    public function moveParticipant(Participant $participant, int $roomId): void
    {
        abort_unless((int) session('participant_id') === $participant->id, 403);

        DB::transaction(function () use ($participant, $roomId) {
            $player = Participant::findOrFail($participant->id);
            $game = GameSession::query()->lockForUpdate()->findOrFail($player->team->game_session_id);
            $player = Participant::query()->lockForUpdate()->findOrFail($player->id);
            abort_if($game->status === GameStatus::Lobby, 403);
            $steps = $player->team->progress()->with('room')->orderBy('id')->get();
            $currentId = app(ParticipantPosition::class)->ensure($player, $steps);
            $current = $steps->firstWhere('room_id', $currentId);
            $target = $steps->firstWhere('room_id', $roomId);
            abort_unless($current && $target?->unlocked_at && $target->room->game_session_id === $game->id, 403);
            if ($currentId === $roomId) {
                return;
            }
            if (abs($current->room->position - $target->room->position) !== 1) {
                throw ValidationException::withMessages(['movement' => 'Elige una sala contigua conectada por un pasillo.']);
            }
            $player->forceFill(['current_room_id' => $roomId, 'last_seen_at' => now()])->save();
        }, attempts: 3);
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
