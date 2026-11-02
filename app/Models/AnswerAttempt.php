<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AnswerAttempt extends Model
{
    protected function casts(): array
    {
        return ['is_correct' => 'boolean'];
    }
}
