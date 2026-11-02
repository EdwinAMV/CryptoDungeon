<?php

namespace App\Services;

use InvalidArgumentException;

class MapGenerator
{
    public function generate(int $count): array
    {
        if ($count < 1 || $count > 24) {
            throw new InvalidArgumentException('El mapa admite entre 1 y 24 habitaciones.');
        }
        $columns = min($count, random_int(3, 5));
        $rows = (int) ceil($count / $columns);
        $mirror = (bool) random_int(0, 1);
        $rooms = [];
        for ($index = 0; $index < $count; $index++) {
            $y = intdiv($index, $columns);
            $x = $y % 2 === 0 ? $index % $columns : $columns - 1 - $index % $columns;
            $rooms[] = ['position' => $index + 1, 'grid_x' => $mirror ? $columns - 1 - $x : $x, 'grid_y' => $y];
        }

        return ['columns' => $columns, 'rows' => $rows, 'rooms' => $rooms];
    }
}
