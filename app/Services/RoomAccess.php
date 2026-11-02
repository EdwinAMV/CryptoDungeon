<?php

namespace App\Services;

use App\Models\GameSession;
use Illuminate\Support\Str;

class RoomAccess
{
    public function ownerHash(bool $create = false): ?string
    {
        $token = session('room_owner_token');

        if (! is_string($token) || strlen($token) !== 64) {
            if (! $create) {
                return null;
            }

            $token = Str::random(64);
            session(['room_owner_token' => $token]);
        }

        return hash('sha256', $token);
    }

    public function canManage(GameSession $game): bool
    {
        $hash = $this->ownerHash();

        return $hash !== null
            && is_string($game->owner_token_hash)
            && hash_equals($game->owner_token_hash, $hash);
    }
}
