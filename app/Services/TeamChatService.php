<?php

namespace App\Services;

use App\Models\GameSession;
use App\Models\Participant;
use App\Models\TeamChatMessage;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class TeamChatService
{
    public function participant(): ?Participant
    {
        $player = Participant::with('team.gameSession')->find(session('participant_id'));

        return $player && ! $player->left_at && $player->team?->gameSession?->mode === 'teams' ? $player : null;
    }

    public function messages(): Collection
    {
        $player = $this->participant();
        abort_unless($player, 403);

        return TeamChatMessage::where('team_id', $player->team_id)->with('participant:id,display_name,color_hue')
            ->latest('id')->limit(50)->get()->reverse()->values();
    }

    public function send(string $body): void
    {
        $player = $this->participant();
        abort_unless($player, 403);
        $data = Validator::make(['message' => trim($body)], [
            'message' => ['required', 'string', 'max:500'],
        ])->validate();
        DB::transaction(function () use ($player, $data) {
            $game = GameSession::query()->lockForUpdate()->findOrFail($player->team->game_session_id);
            $current = Participant::query()->lockForUpdate()->find($player->id);
            abort_unless($current && ! $current->left_at && $current->team_id === $player->team_id && $game->mode === 'teams', 403);
            $until = $this->cooldownUntil($current);
            if ($until > now()->getTimestamp()) {
                throw ValidationException::withMessages(['message' => 'Espera '.($until - now()->getTimestamp()).' segundos antes de enviar otro mensaje.']);
            }
            TeamChatMessage::create([
                'team_id' => $current->team_id, 'participant_id' => $current->id, 'body' => $data['message'],
            ]);
        }, attempts: 3);
    }

    public function cooldownUntil(Participant $player): int
    {
        $last = TeamChatMessage::where('participant_id', $player->id)->latest('id')->first(['created_at']);

        return $last ? $last->created_at->addSeconds(config('cryptodungeon.chat_cooldown_seconds', 4))->getTimestamp() : 0;
    }

    public function forHost(GameSession $game, int $teamId): Collection
    {
        Gate::authorize('view', $game);
        abort_unless($game->mode === 'teams' && $game->teams()->whereKey($teamId)->exists(), 403);

        return TeamChatMessage::where('team_id', $teamId)->with('participant:id,display_name,color_hue')
            ->latest('id')->limit(50)->get()->reverse()->values();
    }
}
