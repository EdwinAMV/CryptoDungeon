<?php

namespace App\Services;

class DungeonMapPresenter
{
    public function present(array $tiles, array $players = [], array $teams = [], bool $interactive = false): array
    {
        $rooms = [];
        $names = ['Entrada', 'Galería', 'Archivo', 'Sala de runas', 'Cripta', 'Armería', 'Biblioteca', 'Santuario', 'Cámara del eco', 'Observatorio', 'Sala del guardián', 'Taller', 'Cámara de cristal', 'Sala del portal', 'Tesoro'];
        usort($tiles, fn ($left, $right) => $left['position'] <=> $right['position']);

        foreach ($tiles as $tile) {
            $width = $tile['position'] % 3 === 0 ? 10 : 8;
            $height = $tile['position'] % 4 === 0 ? 8 : 6;
            $centerX = 9 + $tile['x'] * 14;
            $centerY = 8 + $tile['y'] * 12;
            $rooms[] = [
                ...$tile,
                'name' => $names[($tile['position'] - 1) % count($names)],
                'left' => $centerX - $width / 2, 'top' => $centerY - $height / 2,
                'width' => $width, 'height' => $height, 'center_x' => $centerX, 'center_y' => $centerY,
                'decoration' => ['altar', 'library', 'crystals', 'table'][($tile['position'] - 1) % 4],
                'players' => array_values(array_filter($players, fn ($player) => $player['room_id'] === $tile['id'])),
            ];
        }

        $corridors = [];
        $doors = [];
        for ($index = 1; $index < count($rooms); $index++) {
            $previous = $rooms[$index - 1];
            $room = $rooms[$index];
            $horizontal = $previous['y'] === $room['y'];
            $corridors[] = [
                'left' => $horizontal ? min($previous['center_x'], $room['center_x']) : $room['center_x'] - 1,
                'top' => $horizontal ? $room['center_y'] - 1 : min($previous['center_y'], $room['center_y']),
                'width' => $horizontal ? abs($previous['center_x'] - $room['center_x']) : 2,
                'height' => $horizontal ? 2 : abs($previous['center_y'] - $room['center_y']),
                'open' => $room['state'] !== 'locked',
            ];
            foreach ([[$previous, $room], [$room, $previous]] as [$from, $to]) {
                $doors[] = [
                    'left' => $horizontal ? $from['center_x'] + ($to['center_x'] > $from['center_x'] ? $from['width'] / 2 : -$from['width'] / 2) - 0.5 : $from['center_x'] - 1,
                    'top' => $horizontal ? $from['center_y'] - 1 : $from['center_y'] + ($to['center_y'] > $from['center_y'] ? $from['height'] / 2 : -$from['height'] / 2) - 0.5,
                    'width' => $horizontal ? 1 : 2,
                    'height' => $horizontal ? 2 : 1,
                    'open' => $room['state'] !== 'locked',
                ];
            }
        }

        return [
            'rooms' => $rooms, 'corridors' => $corridors, 'doors' => $doors, 'teams' => $teams,
            'width' => (($tiles === [] ? 0 : max(array_column($tiles, 'x'))) + 1) * 14 + 4,
            'height' => (($tiles === [] ? 0 : max(array_column($tiles, 'y'))) + 1) * 12 + 4,
            'interactive' => $interactive,
        ];
    }
}
