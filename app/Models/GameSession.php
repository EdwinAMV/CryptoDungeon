<?php

namespace App\Models;

use App\Enums\GameStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class GameSession extends Model
{
    protected $fillable = ['teacher_id', 'code', 'mode', 'difficulty', 'duration_minutes', 'status'];

    protected function casts(): array
    {
        return [
            'status' => GameStatus::class,
            'duration_minutes' => 'integer',
            'started_at' => 'immutable_datetime',
            'ends_at' => 'immutable_datetime',
            'finished_at' => 'immutable_datetime',
        ];
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    public function teams(): HasMany
    {
        return $this->hasMany(Team::class);
    }

    public function participants(): HasManyThrough
    {
        return $this->hasManyThrough(Participant::class, Team::class);
    }
}
