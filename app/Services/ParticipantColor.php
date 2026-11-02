<?php

namespace App\Services;

class ParticipantColor
{
    public function choose(array $used): int
    {
        if ($used === []) {
            return random_int(0, 359);
        }
        $best = [];
        $largestDistance = -1;
        foreach (range(0, 359) as $hue) {
            $distance = min(array_map(fn ($other) => min(abs($hue - $other), 360 - abs($hue - $other)), $used));
            if ($distance > $largestDistance) {
                $largestDistance = $distance;
                $best = [$hue];
            } elseif ($distance === $largestDistance) {
                $best[] = $hue;
            }
        }

        return $best[random_int(0, count($best) - 1)];
    }
}
