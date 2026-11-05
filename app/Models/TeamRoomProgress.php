<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TeamRoomProgress extends Model
{
    public function hints(): HasMany
    {
        return $this->hasMany(HintUsage::class, 'team_room_progress_id');
    }

    public function attempts(): HasMany
    {
        return $this->hasMany(AnswerAttempt::class, 'team_room_progress_id');
    }

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
