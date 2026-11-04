<?php

namespace App\Services;

use App\Support\PuzzleText;
use InvalidArgumentException;

class PhraseGenerator
{
    public function generateMany(int $count): array
    {
        $pool = [];
        foreach (require resource_path('data/phrases.php') as $topic) {
            array_push($pool, ...array_map(PuzzleText::sentence(...), $topic['phrases']));
        }
        $pool = array_values(array_unique($pool));
        if ($count < 1 || $count > count($pool)) {
            throw new InvalidArgumentException('No hay suficientes frases distintas para esta partida.');
        }
        shuffle($pool);

        return array_slice($pool, 0, $count);
    }
}
