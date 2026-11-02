<?php

namespace App\Services;

use App\Ciphers\CaesarCipher;
use App\Ciphers\SimpleSubstitutionCipher;
use App\Ciphers\VigenereCipher;
use App\Enums\CipherType;

class PuzzleGenerator
{
    public function countForDifficulty(string $difficulty): int
    {
        return config('cryptodungeon.puzzles_by_difficulty')[$difficulty]
            ?? throw new \InvalidArgumentException('Dificultad desconocida.');
    }

    public function generate(CipherType $cipher, string $phrase): array
    {
        $parameters = match ($cipher) {
            CipherType::Caesar => ['shift' => random_int(1, 25)],
            CipherType::SimpleSubstitution => ['alphabet' => $this->substitutionAlphabet()],
            CipherType::Vigenere => ['key' => ['RUNA', 'TORRE', 'CLAVE', 'DRAGON', 'CRISTAL'][random_int(0, 4)]],
        };
        $ciphertext = match ($cipher) {
            CipherType::Caesar => (new CaesarCipher)->encrypt($phrase, $parameters['shift']),
            CipherType::SimpleSubstitution => (new SimpleSubstitutionCipher)->encrypt($phrase, $parameters['alphabet']),
            CipherType::Vigenere => (new VigenereCipher)->encrypt($phrase, $parameters['key']),
        };

        return ['cipher_type' => $cipher, 'ciphertext' => $ciphertext, 'public_parameters' => $parameters, 'solution' => $phrase];
    }

    private function substitutionAlphabet(): string
    {
        do {
            $letters = range('A', 'Z');
            shuffle($letters);
            $fixedPoints = array_filter($letters, fn ($letter, $index) => $letter === chr(65 + $index), ARRAY_FILTER_USE_BOTH);
        } while ($fixedPoints !== []);

        return implode('', $letters);
    }
}
