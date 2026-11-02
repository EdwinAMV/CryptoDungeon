<?php

namespace App\Services;

class ScoreCalculator
{
    public function solvedRoom(int $incorrectAttempts = 0): int
    {
        return max(0, 100 - max(0, $incorrectAttempts) * 10);
    }
}
