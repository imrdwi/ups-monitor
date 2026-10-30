<?php

namespace Tests\Feature;

use App\Jobs\PollUpsUnit;
use App\Models\UpsReading;
use App\Models\UpsUnit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PollUpsUnitTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function a_successful_poll_stores_a_reading_and_clears_the_failure_streak(): void
    {
        Http::fake(['*' => Http::response(file_get_contents(base_path('tests/Fixtures/three-phase-status.html')))]);
        $unit = UpsUnit::factory()->html()->threePhase()->create();
        $unit->forceFill(['consecutive_failures' => 3, 'last_error' => 'timeout'])->save();

        PollUpsUnit::dispatchSync($unit);

        $unit->refresh();
        $this->assertSame(1, $unit->readings()->count());
        $this->assertSame(27.5, $unit->latestReading->temperature_celsius);
        $this->assertSame(0, $unit->consecutive_failures);
        $this->assertNull($unit->last_error);
        $this->assertFalse($unit->isStale());
    }

    #[Test]
    public function a_failed_poll_is_recorded_and_keeps_the_last_good_reading(): void
    {
        Http::fake(['*' => Http::failedConnection()]);
        $unit = UpsUnit::factory()->html()->create();
        $lastGood = UpsReading::factory()->for($unit)->create(['recorded_at' => now()->subMinutes(10)]);
        $unit->forceFill(['last_reading_at' => $lastGood->recorded_at])->save();

        PollUpsUnit::dispatchSync($unit);
        PollUpsUnit::dispatchSync($unit);

        $unit->refresh();
        $this->assertSame(2, $unit->consecutive_failures);
        $this->assertNotNull($unit->last_error);
        $this->assertSame($lastGood->id, $unit->latestReading->id);
        $this->assertTrue($unit->isStale());
    }

    #[Test]
    public function one_unreachable_unit_does_not_stop_the_others(): void
    {
        Http::fake([
            'down.example.test/*' => Http::failedConnection(),
            'up.example.test/*' => Http::response(file_get_contents(base_path('tests/Fixtures/single-phase-status.html'))),
        ]);
        $down = UpsUnit::factory()->html('http://down.example.test/status.htm')->create();
        $up = UpsUnit::factory()->html('http://up.example.test/status.htm')->create();
        $simulated = UpsUnit::factory()->create();

        $this->artisan('ups:poll')->assertSuccessful();

        $this->assertSame(0, $down->readings()->count());
        $this->assertSame(1, $down->fresh()->consecutive_failures);
        $this->assertSame(1, $up->readings()->count());
        $this->assertSame(1, $simulated->readings()->count());
    }

    #[Test]
    public function the_command_queues_one_job_per_active_unit(): void
    {
        Queue::fake();
        $active = UpsUnit::factory()->count(2)->create();
        UpsUnit::factory()->inactive()->create();

        $this->artisan('ups:poll')->expectsOutputToContain('Queued 2 UPS polls.')->assertSuccessful();

        Queue::assertPushed(PollUpsUnit::class, 2);
        Queue::assertPushed(PollUpsUnit::class, fn (PollUpsUnit $job) => $active->contains($job->unit));
    }

    #[Test]
    public function an_unknown_driver_is_recorded_as_a_failure(): void
    {
        $unit = UpsUnit::factory()->create(['driver' => 'snmp-v9']);

        PollUpsUnit::dispatchSync($unit);

        $this->assertStringContainsString('Unknown UPS driver', $unit->fresh()->last_error);
    }

    #[Test]
    public function readings_older_than_the_retention_window_are_pruned(): void
    {
        config(['ups.retention_days' => 30]);
        $old = UpsReading::factory()->create(['recorded_at' => now()->subDays(31)]);
        $recent = UpsReading::factory()->create(['recorded_at' => now()->subDays(29)]);

        $this->artisan('model:prune', ['--model' => [UpsReading::class]])->assertSuccessful();

        $this->assertModelMissing($old);
        $this->assertModelExists($recent);
    }
}
