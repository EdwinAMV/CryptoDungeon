<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
}
