<?php

namespace Tests\Unit;

use App\Ciphers\CaesarCipher;
use App\Ciphers\SimpleSubstitutionCipher;
use App\Ciphers\VigenereCipher;
use App\Services\AnswerValidator;
use PHPUnit\Framework\TestCase;

class CiphersTest extends TestCase
{
    public function test_caesar_wraps_the_alphabet_and_preserves_spaces(): void
    {
        $this->assertSame('DWWDFN DW GDZQ', (new CaesarCipher)->encrypt('ATTACK AT DAWN', 3));
        $this->assertSame('ABC XYZ', (new CaesarCipher)->encrypt('XYZ UVW', 3));
    }

    public function test_vigenere_matches_the_classic_known_vector_and_skips_spaces(): void
    {
        $cipher = new VigenereCipher;
        $this->assertSame('LXFOPVEFRNHR', $cipher->encrypt('ATTACKATDAWN', 'LEMON'));
        $this->assertSame('LXFOPV EF RNHR', $cipher->encrypt('ATTACK AT DAWN', 'LEMON'));
    }

    public function test_simple_substitution_uses_a_single_bijective_alphabet(): void
    {
        $this->assertSame('QWE QWE', (new SimpleSubstitutionCipher)->encrypt('ABC ABC', 'QWERTYUIOPASDFGHJKLZXCVBNM'));
    }

    public function test_invalid_substitution_alphabets_are_rejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        (new SimpleSubstitutionCipher)->encrypt('ABC', str_repeat('A', 26));
    }

    public function test_answers_ignore_accents_case_spacing_and_punctuation(): void
    {
        $validator = new AnswerValidator;
        $this->assertTrue($validator->matches('¡La llave del jardín!', 'LA LLAVE DEL JARDIN'));
        $this->assertFalse($validator->matches('LA LLAVE', 'LA LLAVE DEL JARDIN'));
        $this->assertFalse($validator->matches('!!!', 'LA LLAVE DEL JARDIN'));
    }
}
