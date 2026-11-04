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

    public function test_beta_bank_produces_short_distinct_sentences_from_all_seven_topics(): void
    {
        $bank = require resource_path('data/phrases.php');
        $this->assertCount(7, $bank);
        foreach ($bank as $topic) {
            $this->assertNotEmpty($topic['reference']);
            $this->assertCount(20, $topic['phrases']);
        }
        $phrases = (new PhraseGenerator)->generateMany(140);
        $this->assertCount(140, array_unique($phrases));
        foreach ($phrases as $phrase) {
            $this->assertMatchesRegularExpression('/\A[A-Z]+(?: [A-Z]+)+\z/', $phrase);
            $this->assertLessThanOrEqual(50, strlen($phrase));
            $this->assertGreaterThanOrEqual(4, str_word_count($phrase));
            $this->assertLessThanOrEqual(7, str_word_count($phrase));
        }
        $selection = (new PhraseGenerator)->generateMany(15);
        $this->assertCount(15, array_unique($selection));
        $this->assertSame([], array_diff($selection, $phrases));
    }

    public function test_generation_rejects_requests_larger_than_the_bank_instead_of_repeating(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        (new PhraseGenerator)->generateMany(141);
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
