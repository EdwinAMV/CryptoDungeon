<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class Team extends Model
{
    protected $fillable = ['name'];

    protected function casts(): array
    {
        return ['finished_at' => 'immutable_datetime'];
    }

    public function progress(): HasMany
    {
        return $this->hasMany(TeamRoomProgress::class);
    }

    public function gameSession(): BelongsTo
    {
        return $this->belongsTo(GameSession::class);
    }

    public function participants(): HasMany
    {
        return $this->hasMany(Participant::class);
    }

    public function attempts(): HasManyThrough
    {
        return $this->hasManyThrough(AnswerAttempt::class, TeamRoomProgress::class, 'team_id', 'team_room_progress_id');
    }

    public function hints(): HasManyThrough
    {
        return $this->hasManyThrough(HintUsage::class, TeamRoomProgress::class, 'team_id', 'team_room_progress_id');
    }
}
