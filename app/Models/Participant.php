<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Participant extends Model
{
    protected $fillable = ['display_name', 'last_seen_at'];

    protected function casts(): array
    {
        return ['last_seen_at' => 'immutable_datetime', 'left_at' => 'immutable_datetime', 'color_hue' => 'integer'];
    }

    public function displayColor(): string
    {
        $hue = $this->color_hue ?? (($this->id * 137) % 360);

        return 'hsl('.$hue.' 70% 72%)';
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function currentRoom(): BelongsTo
    {
        return $this->belongsTo(Room::class, 'current_room_id');
    }

    public function attempts(): HasMany
    {
        return $this->hasMany(AnswerAttempt::class);
    }

    public function hints(): HasMany
    {
        return $this->hasMany(HintUsage::class);
    }
}
