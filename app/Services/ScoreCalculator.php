<?php

namespace App\Services;

class ScoreCalculator
{
    public function solvedRoom(int $incorrectAttempts = 0, int $hintPenalty = 0): int
    {
        return 100 - max(0, $incorrectAttempts) * 10 - max(0, $hintPenalty);
    }
}
