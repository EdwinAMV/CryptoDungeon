<?php

namespace App\Policies;

use App\Models\GameSession;
use App\Models\User;
use App\Services\RoomAccess;

class GameSessionPolicy
{
    public function view(?User $user, GameSession $gameSession): bool
    {
        return app(RoomAccess::class)->canManage($gameSession);
    }

    public function update(?User $user, GameSession $gameSession): bool
    {
        return $this->view($user, $gameSession);
    }
}
