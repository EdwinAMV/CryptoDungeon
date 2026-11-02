<?php

namespace App\Ciphers;

use InvalidArgumentException;

class SimpleSubstitutionCipher
{
    public function encrypt(string $text, string $alphabet): string
    {
        $letters = str_split($alphabet);
        $sorted = $letters;
        sort($sorted);
        if (implode('', $sorted) !== 'ABCDEFGHIJKLMNOPQRSTUVWXYZ') {
            throw new InvalidArgumentException('El alfabeto debe ser una permutación de las 26 letras.');
        }

        return strtr($text, array_combine(range('A', 'Z'), $letters));
    }
}
