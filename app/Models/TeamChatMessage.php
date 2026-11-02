<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TeamChatMessage extends Model
{
    protected $fillable = ['team_id', 'participant_id', 'body'];

    public function participant(): BelongsTo
    {
        return $this->belongsTo(Participant::class);
    }
}
