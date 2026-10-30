<?php

namespace App\Jobs;

use App\Models\UpsUnit;
use App\Readers\ReaderManager;
use App\Readers\UnreadableStatusPage;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Http\Client\ConnectionException;
use InvalidArgumentException;

/**
 * Polls one unit. Each unit gets its own job, so a slow or unreachable
 * card delays only its own reading, not the rest of the fleet.
 */
class PollUpsUnit implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    /**
     * A failed poll is recorded and retried by the next scheduled run, not by the queue.
     */
    public int $tries = 1;

    public int $timeout = 30;

    /**
     * Skip dispatching while the previous minute's poll for this unit is still queued or running.
     */
    public int $uniqueFor = 55;

    public function __construct(public UpsUnit $unit) {}

    public function uniqueId(): string
    {
        return (string) $this->unit->getKey();
    }

    public function handle(ReaderManager $readers): void
    {
        try {
            $data = $readers->for($this->unit)->read($this->unit);
        } catch (ConnectionException|UnreadableStatusPage|InvalidArgumentException $exception) {
            $this->unit->recordFailure($exception->getMessage());

            return;
        }

        $this->unit->recordReading($data);
    }
}
