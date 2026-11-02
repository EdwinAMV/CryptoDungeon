<?php

namespace App\Services;

class ScoreCalculator
{
    public function solvedRoom(int $incorrectAttempts = 0): int
    {
        return 100 - max(0, $incorrectAttempts) * 10;
    }
}
