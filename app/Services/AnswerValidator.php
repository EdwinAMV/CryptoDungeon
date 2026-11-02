<?php

namespace App\Services;

use App\Support\PuzzleText;

class AnswerValidator
{
    public function matches(string $answer, string $solution): bool
    {
        return hash_equals(PuzzleText::answer($solution), PuzzleText::answer($answer));
    }
}
