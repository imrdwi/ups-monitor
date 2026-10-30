<?php

namespace App\Console\Commands;

use App\Jobs\PollUpsUnit;
use App\Models\UpsUnit;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('ups:poll')]
#[Description('Queue a status poll for every active UPS unit')]
class PollUpsUnits extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $count = 0;

        UpsUnit::query()->active()->each(function (UpsUnit $unit) use (&$count): void {
            PollUpsUnit::dispatch($unit);
            $count++;
        });

        $this->components->info("Queued {$count} UPS polls.");

        return self::SUCCESS;
    }
}
