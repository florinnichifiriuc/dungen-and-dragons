<?php

namespace App\Services;

use InvalidArgumentException;

class DiceRoller
{
    public function roll(string $expression): array
    {
        $normalized = strtolower($expression);

        if (! preg_match('/^(?P<count>[0-9]*)d(?P<sides>[0-9]+)(?P<modifier>[+-][0-9]+)?$/', $normalized, $matches)) {
            throw new InvalidArgumentException('Invalid dice expression.');
        }

        $count = $matches['count'] !== '' ? (int) $matches['count'] : 1;
        $sides = (int) $matches['sides'];
        $modifier = isset($matches['modifier']) ? (int) $matches['modifier'] : 0;

        if ($count <= 0 || $sides <= 0) {
            throw new InvalidArgumentException('Dice count and sides must be positive integers.');
        }

        $rolls = [];
        for ($i = 0; $i < $count; $i++) {
            $rolls[] = random_int(1, $sides);
        }

        $total = array_sum($rolls) + $modifier;

        return [
            'rolls' => $rolls,
            'modifier' => $modifier,
            'total' => $total,
        ];
    }
}
