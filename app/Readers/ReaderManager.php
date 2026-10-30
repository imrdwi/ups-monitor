<?php

namespace App\Readers;

use App\Models\UpsUnit;
use InvalidArgumentException;

class ReaderManager
{
    public function for(UpsUnit $unit): UpsReader
    {
        return match ($unit->driver) {
            'html' => app(HtmlStatusPageReader::class),
            'simulated' => app(SimulatedReader::class),
            default => throw new InvalidArgumentException("Unknown UPS driver [{$unit->driver}] on {$unit->serial_number}."),
        };
    }
}
