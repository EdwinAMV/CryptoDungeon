<?php

namespace App\Ciphers;

class CaesarCipher
{
    public function encrypt(string $text, int $shift): string
    {
        $shift = (($shift % 26) + 26) % 26;

        return preg_replace_callback('/[A-Z]/', fn ($match) => chr(65 + (ord($match[0]) - 65 + $shift) % 26), $text);
    }
}
