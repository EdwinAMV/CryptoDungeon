<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HintUsage extends Model
{
    protected $fillable = ['participant_id', 'level', 'penalty', 'character_position'];

    protected function casts(): array
    {
        return ['level' => 'integer', 'penalty' => 'integer', 'character_position' => 'integer'];
    }
}
