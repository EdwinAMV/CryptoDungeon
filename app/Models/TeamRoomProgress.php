<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TeamRoomProgress extends Model
{
    protected $table = 'team_room_progress';

    protected $fillable = ['room_id', 'unlocked_at', 'solved_at', 'awarded_points'];

    protected function casts(): array
    {
        return ['unlocked_at' => 'immutable_datetime', 'solved_at' => 'immutable_datetime', 'awarded_points' => 'integer'];
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }
}
