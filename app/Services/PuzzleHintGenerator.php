<?php

namespace App\Services;

use App\Enums\CipherType;
use App\Models\Room;
use InvalidArgumentException;

class PuzzleHintGenerator
{
    // Decode only the purchased occurrence. Spaces never consume the Vigenere key.
    public function letterAt(Room $room, int $position): string
    {
        $text = $room->ciphertext;
        if ($position < 0 || $position >= strlen($text) || ! preg_match('/\A[A-Z]\z/', $text[$position])) {
            throw new InvalidArgumentException('Selecciona una posición que contenga una letra.');
        }
        $letter = $text[$position];
        if ($room->cipher_type === CipherType::SimpleSubstitution) {
            return chr(65 + strpos($room->public_parameters['alphabet'], $letter));
        }
        $shift = $room->public_parameters['shift'] ?? 0;
        if ($room->cipher_type === CipherType::Vigenere) {
            $key = $room->public_parameters['key'];
            $letterIndex = strlen(preg_replace('/[^A-Z]/', '', substr($text, 0, $position)));
            $shift = ord($key[$letterIndex % strlen($key)]) - 65;
        }

        return chr(65 + (ord($letter) - 65 - $shift + 26) % 26);
    }

    public function legacyPositions(Room $room, int $level): array
    {
        preg_match_all('/[A-Z]/', $room->ciphertext, $matches, PREG_OFFSET_CAPTURE);
        $limit = min($level === 1 ? 1 : 6, intdiv(count($matches[0]), 2));

        return array_column(array_slice($matches[0], 0, $limit), 1);
    }
}
