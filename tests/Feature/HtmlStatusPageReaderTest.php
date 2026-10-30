<?php

namespace Tests\Feature;

use App\Models\UpsUnit;
use App\Readers\HtmlStatusPageReader;
use App\Readers\UnreadableStatusPage;
use App\UpsStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class HtmlStatusPageReaderTest extends TestCase
{
    use RefreshDatabase;

    private HtmlStatusPageReader $reader;

    protected function setUp(): void
    {
        parent::setUp();

        $this->reader = new HtmlStatusPageReader;
    }

    #[Test]
    public function it_reads_a_three_phase_status_page(): void
    {
        $unit = UpsUnit::factory()->html()->threePhase()->create(['rated_power_va' => 40_000]);

        $reading = $this->reader->parse($this->fixture('three-phase-status.html'), $unit);

        $this->assertSame(UpsStatus::Online, $reading->status);
        $this->assertSame(222.0, $reading->inputVoltage);
        $this->assertSame(220.0, $reading->outputVoltage);
        $this->assertSame(20.0, $reading->outputCurrent);
        $this->assertSame(50.0, $reading->frequencyHz);
        $this->assertSame(27.5, $reading->temperatureCelsius);
        $this->assertSame(100.0, $reading->batteryCapacityPercent);
        $this->assertSame(272.4, $reading->batteryVoltage);
        $this->assertSame(65, $reading->batteryRuntimeMinutes);
        $this->assertSame('10/ 20/ 30', $reading->raw['output_current']);
    }

    #[Test]
    public function without_a_load_field_load_is_derived_from_output_power(): void
    {
        $unit = UpsUnit::factory()->html()->threePhase()->create(['rated_power_va' => 40_000]);

        $reading = $this->reader->parse($this->fixture('three-phase-status.html'), $unit);

        // 220 V × (10 + 20 + 30) A = 13,200 VA of a 40,000 VA rating.
        $this->assertSame(33.0, $reading->loadPercent);
    }

    #[Test]
    public function it_reads_a_single_phase_page_with_header_labels(): void
    {
        $unit = UpsUnit::factory()->html()->create();

        $reading = $this->reader->parse($this->fixture('single-phase-status.html'), $unit);

        $this->assertSame(UpsStatus::OnBattery, $reading->status);
        $this->assertSame(42.0, $reading->loadPercent);
        $this->assertSame(49.9, $reading->frequencyHz);
        $this->assertSame(35, $reading->batteryRuntimeMinutes);
        $this->assertNull($reading->temperatureCelsius);
    }

    #[Test]
    public function a_status_page_served_with_an_error_code_is_still_read(): void
    {
        Http::fake(['*' => Http::response($this->fixture('three-phase-status.html'), 500)]);
        $unit = UpsUnit::factory()->html()->threePhase()->create();

        $this->assertSame(UpsStatus::Online, $this->reader->read($unit)->status);
    }

    #[Test]
    public function a_response_without_a_status_table_is_rejected(): void
    {
        Http::fake(['*' => Http::response('<html><body>Login required</body></html>', 401)]);
        $unit = UpsUnit::factory()->html()->create();

        $this->expectException(UnreadableStatusPage::class);

        $this->reader->read($unit);
    }

    #[Test]
    public function a_table_without_a_status_field_is_rejected(): void
    {
        $unit = UpsUnit::factory()->html()->create();

        $this->expectException(UnreadableStatusPage::class);

        $this->reader->parse('<table><tr><td>Uptime</td><td>4 days</td></tr></table>', $unit);
    }

    #[Test]
    public function an_unreachable_card_raises_a_connection_error(): void
    {
        Http::fake(['*' => Http::failedConnection()]);
        $unit = UpsUnit::factory()->html()->create();

        $this->expectException(ConnectionException::class);

        $this->reader->read($unit);
    }

    private function fixture(string $name): string
    {
        return file_get_contents(base_path("tests/Fixtures/{$name}"));
    }
}
