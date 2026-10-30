<?php

namespace App;

/**
 * Battery bank condition from a maintenance check, by share of blocks that are not OK.
 */
enum BatteryHealth: string
{
    case Normal = 'normal';
    case Minor = 'minor';
    case Major = 'major';

    /**
     * Normal when every block is OK; minor while at most 10% are low or bad; major beyond that.
     */
    public static function fromBlocks(int $total, int $low, int $bad): self
    {
        $failing = $low + $bad;

        return match (true) {
            $total === 0 || $failing === 0 => self::Normal,
            $failing / $total <= 0.10 => self::Minor,
            default => self::Major,
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::Normal => 'Normal',
            self::Minor => 'Minor issue',
            self::Major => 'Major issue',
        };
    }
}
