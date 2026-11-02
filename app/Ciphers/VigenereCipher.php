<?php

namespace App\Ciphers;

use InvalidArgumentException;

class VigenereCipher
{
    public function encrypt(string $text, string $key): string
    {
        if (! preg_match('/\A[A-Z]+\z/', $key)) {
            throw new InvalidArgumentException('La clave debe contener letras A-Z.');
        }

        $position = 0;

        return preg_replace_callback('/[A-Z]/', function ($match) use ($key, &$position) {
            $shift = ord($key[$position++ % strlen($key)]) - 65;

            return chr(65 + (ord($match[0]) - 65 + $shift) % 26);
        }, $text);
    }
}
