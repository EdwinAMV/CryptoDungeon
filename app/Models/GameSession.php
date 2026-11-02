<?php

namespace App\Models;

use App\Enums\GameStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class GameSession extends Model
{
    protected $fillable = ['owner_token_hash', 'code', 'mode', 'difficulty', 'status'];

    protected $hidden = ['owner_token_hash'];

    protected function casts(): array
    {
        return [
            'status' => GameStatus::class,
            'duration_minutes' => 'integer',
            'map_layout' => 'array',
            'started_at' => 'immutable_datetime',
            'ends_at' => 'immutable_datetime',
            'finished_at' => 'immutable_datetime',
        ];
    }

    public function teams(): HasMany
    {
        return $this->hasMany(Team::class);
    }

    public function rooms(): HasMany
    {
        return $this->hasMany(Room::class)->orderBy('position');
    }

    public function participants(): HasManyThrough
    {
        return $this->hasManyThrough(Participant::class, Team::class);
    }
}
