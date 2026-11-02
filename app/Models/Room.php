<?php

namespace App\Models;

use App\Enums\CipherType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Room extends Model
{
    protected $fillable = ['position', 'grid_x', 'grid_y', 'cipher_type', 'ciphertext', 'public_parameters', 'solution'];

    protected $hidden = ['solution'];

    protected function casts(): array
    {
        return ['cipher_type' => CipherType::class, 'public_parameters' => 'array', 'solution' => 'encrypted'];
    }

    public function gameSession(): BelongsTo
    {
        return $this->belongsTo(GameSession::class);
    }

    public function publicPuzzle(): array
    {
        return [
            'id' => $this->id, 'position' => $this->position,
            'cipher' => $this->cipher_type->value, 'label' => $this->cipher_type->label(),
            'ciphertext' => $this->ciphertext, 'parameters' => $this->public_parameters,
        ];
    }
}
