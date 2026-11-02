<?php

namespace Tests\Unit;

use App\Enums\CipherType;
use App\Services\MapGenerator;
use App\Services\PhraseGenerator;
use App\Services\PuzzleGenerator;
use Tests\TestCase;

class PuzzleGenerationTest extends TestCase
{
    public function test_difficulty_only_controls_the_number_of_rooms(): void
    {
        $generator = new PuzzleGenerator;
        $this->assertSame(5, $generator->countForDifficulty('easy'));
        $this->assertSame(10, $generator->countForDifficulty('normal'));
        $this->assertSame(15, $generator->countForDifficulty('hard'));
    }

    public function test_phrase_templates_produce_distinct_complete_sentences(): void
    {
        $phrases = (new PhraseGenerator)->generateMany(15);
        $this->assertCount(15, array_unique($phrases));
        foreach ($phrases as $phrase) {
            $this->assertMatchesRegularExpression('/\A[A-Z]+(?: [A-Z]+)+\z/', $phrase);
            $this->assertLessThan(100, strlen($phrase));
        }
    }

    public function test_maps_always_have_unique_cells_and_a_contiguous_path(): void
    {
        foreach ([5, 10, 15] as $count) {
            for ($iteration = 0; $iteration < 10; $iteration++) {
                $map = (new MapGenerator)->generate($count);
                $this->assertCount($count, $map['rooms']);
                $cells = [];
                foreach ($map['rooms'] as $index => $room) {
                    $this->assertSame($index + 1, $room['position']);
                    $cells[] = $room['grid_x'].','.$room['grid_y'];
                    if ($index > 0) {
                        $previous = $map['rooms'][$index - 1];
                        $this->assertSame(1, abs($room['grid_x'] - $previous['grid_x']) + abs($room['grid_y'] - $previous['grid_y']));
                    }
                }
                $this->assertCount($count, array_unique($cells));
            }
        }
    }

    public function test_generated_puzzles_use_only_the_requested_ciphers(): void
    {
        $generator = new PuzzleGenerator;
        foreach (CipherType::cases() as $cipher) {
            $puzzle = $generator->generate($cipher, 'LA LLAVE ABRE LA PUERTA');
            $this->assertSame($cipher, $puzzle['cipher_type']);
            $this->assertNotSame($puzzle['solution'], $puzzle['ciphertext']);
            if ($cipher === CipherType::SimpleSubstitution) {
                $this->assertSame(26, count(array_unique(str_split($puzzle['public_parameters']['alphabet']))));
            }
        }
    }
}
