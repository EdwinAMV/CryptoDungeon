<?php

namespace App\Support;

use Illuminate\Support\Str;

class PuzzleText
{
    public static function sentence(string $text): string
    {
        return trim(preg_replace('/[^A-Z0-9]+/', ' ', strtoupper(Str::ascii($text))));
    }

    public static function answer(string $text): string
    {
        return str_replace(' ', '', self::sentence($text));
    }
}
