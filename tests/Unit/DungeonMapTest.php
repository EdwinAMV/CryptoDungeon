<?php

namespace Tests\Unit;

use App\Services\DungeonMapPresenter;
use App\Services\MapGenerator;
use PHPUnit\Framework\TestCase;

class DungeonMapTest extends TestCase
{
    public function test_every_layout_has_non_overlapping_rooms_and_straight_connecting_corridors(): void
    {
        foreach ([5, 10, 15] as $count) {
            for ($iteration = 0; $iteration < 10; $iteration++) {
                $layout = (new MapGenerator)->generate($count);
                $tiles = array_map(fn ($room) => [
                    'id' => $room['position'], 'position' => $room['position'],
                    'x' => $room['grid_x'], 'y' => $room['grid_y'], 'state' => 'observed',
                ], $layout['rooms']);
                $map = (new DungeonMapPresenter)->present($tiles);
                $this->assertCount($count - 1, $map['corridors']);
                foreach ($map['rooms'] as $index => $room) {
                    $this->assertGreaterThanOrEqual(0, $room['left']);
                    $this->assertGreaterThanOrEqual(0, $room['top']);
                    $this->assertLessThanOrEqual($map['width'], $room['left'] + $room['width']);
                    $this->assertLessThanOrEqual($map['height'], $room['top'] + $room['height']);
                    foreach (array_slice($map['rooms'], $index + 1) as $other) {
                        $this->assertFalse($room['left'] < $other['left'] + $other['width'] && $other['left'] < $room['left'] + $room['width']
                            && $room['top'] < $other['top'] + $other['height'] && $other['top'] < $room['top'] + $room['height']);
                    }
                    if ($index > 0) {
                        $corridor = $map['corridors'][$index - 1];
                        $this->assertTrue($corridor['width'] === 2 || $corridor['height'] === 2);
                        foreach ([$map['rooms'][$index - 1], $room] as $endpoint) {
                            $this->assertGreaterThanOrEqual($corridor['left'], $endpoint['center_x']);
                            $this->assertLessThanOrEqual($corridor['left'] + $corridor['width'], $endpoint['center_x']);
                            $this->assertGreaterThanOrEqual($corridor['top'], $endpoint['center_y']);
                            $this->assertLessThanOrEqual($corridor['top'] + $corridor['height'], $endpoint['center_y']);
                        }
                    }
                }
            }
        }
    }
}
