<?php

namespace Tests\Unit;

use App\Enums\CipherType;
use App\Models\Room;
use App\Services\PuzzleGenerator;
use App\Services\PuzzleHintGenerator;
use Tests\TestCase;

class PuzzleHintGeneratorTest extends TestCase
{
    public function test_arbitrary_positions_decode_correctly_for_all_three_ciphers(): void
    {
        $plain = 'LA RAM ES VOLATIL';
        foreach (CipherType::cases() as $cipher) {
            $room = (new Room)->forceFill((new PuzzleGenerator)->generate($cipher, $plain));
            foreach (str_split($plain) as $position => $letter) {
                if ($letter !== ' ') {
                    $this->assertSame($letter, (new PuzzleHintGenerator)->letterAt($room, $position));
                }
            }
        }
    }

    public function test_spaces_are_not_valid_purchases(): void
    {
        $room = (new Room)->forceFill((new PuzzleGenerator)->generate(CipherType::Caesar, 'LA RAM ES VOLATIL'));
        $this->expectException(\InvalidArgumentException::class);
        (new PuzzleHintGenerator)->letterAt($room, 2);
    }

    public function test_legacy_prefixes_stay_bounded_and_preserve_their_character_positions(): void
    {
        $room = (new Room)->forceFill((new PuzzleGenerator)->generate(CipherType::Vigenere, 'LA RAM ES VOLATIL'));
        $generator = new PuzzleHintGenerator;
        $this->assertSame([0], $generator->legacyPositions($room, 1));
        $this->assertSame([0, 1, 3, 4, 5, 7], $generator->legacyPositions($room, 2));
    }
}
