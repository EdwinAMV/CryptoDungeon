<?php

namespace Tests\Unit;

use App\Ciphers\CaesarCipher;
use App\Ciphers\SimpleSubstitutionCipher;
use App\Ciphers\VigenereCipher;
use App\Enums\CipherType;
use App\Models\Room;
use App\Services\PuzzleSolutionGuide;
use Tests\TestCase;

class PuzzleSolutionGuideTest extends TestCase
{
    public function test_caesar_examples_use_the_saved_shift_and_wrap_around_the_alphabet(): void
    {
        $room = (new Room)->forceFill([
            'cipher_type' => CipherType::Caesar, 'public_parameters' => ['shift' => 25],
            'ciphertext' => (new CaesarCipher)->encrypt('AZ ZA', 25),
        ]);
        $guide = (new PuzzleSolutionGuide)->describe($room);
        $this->assertStringContainsString('desplazamiento = 25', $guide);
        $this->assertStringContainsString('Z (25) - 25 => 0 (A)', $guide);
        $this->assertStringContainsString('Y (24) - 25 => 25 (Z)', $guide);
    }

    public function test_substitution_reads_the_saved_alphabet_in_reverse(): void
    {
        $alphabet = 'QWERTYUIOPASDFGHJKLZXCVBNM';
        $room = (new Room)->forceFill([
            'cipher_type' => CipherType::SimpleSubstitution, 'public_parameters' => ['alphabet' => $alphabet],
            'ciphertext' => (new SimpleSubstitutionCipher)->encrypt('AB Z', $alphabet),
        ]);
        $guide = (new PuzzleSolutionGuide)->describe($room);
        $this->assertStringContainsString($alphabet, $guide);
        $this->assertStringContainsString('Q => A', $guide);
        $this->assertStringContainsString('W => B', $guide);
        $this->assertStringContainsString('M => Z', $guide);
    }

    public function test_vigenere_aligns_the_key_across_spaces_and_decodes_negative_differences(): void
    {
        $room = (new Room)->forceFill([
            'cipher_type' => CipherType::Vigenere, 'public_parameters' => ['key' => 'RUNA'],
            'ciphertext' => (new VigenereCipher)->encrypt('Z AZ A', 'RUNA'),
        ]);
        $guide = (new PuzzleSolutionGuide)->describe($room);
        $this->assertStringContainsString('clave = RUNA', $guide);
        $this->assertStringContainsString('Clave:   R UN A', $guide);
        $this->assertStringContainsString('Claro:   Z AZ A', $guide);
        $this->assertStringContainsString('Q (16) - R (17) => 25 (Z)', $guide);
        $this->assertStringContainsString('M (12) - N (13) => 25 (Z)', $guide);
    }
}
