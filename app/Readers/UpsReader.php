<?php

namespace App\Readers;

use App\Models\UpsUnit;
use Illuminate\Http\Client\ConnectionException;

interface UpsReader
{
    /**
     * Take one reading from the unit.
     *
     * @throws UnreadableStatusPage when the device answers with something that is not a status page.
     * @throws ConnectionException when the device cannot be reached.
     */
    public function read(UpsUnit $unit): ReadingData;
}
