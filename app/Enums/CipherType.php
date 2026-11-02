<?php

namespace App\Enums;

enum CipherType: string
{
    case Caesar = 'caesar';
    case SimpleSubstitution = 'simple_substitution';
    case Vigenere = 'vigenere';

    public function label(): string
    {
        return match ($this) {
            self::Caesar => 'Cifrado César',
            self::SimpleSubstitution => 'Cifrado de Sustitución Simple',
            self::Vigenere => 'Cifrado de Vigenère',
        };
    }
}
