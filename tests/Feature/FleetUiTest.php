<?php

namespace Tests\Feature;

use App\BatteryHealth;
use App\Models\MaintenanceReport;
use App\Models\Region;
use App\Models\Site;
use App\Models\UpsReading;
use App\Models\UpsUnit;
use App\Models\User;
use App\Readers\SimulatedReader;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class FleetUiTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $technician;

    private UpsUnit $unit;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->admin()->create();
        $this->technician = User::factory()->create();
        $this->unit = UpsUnit::factory()->create();
    }

    /**
     * @return array<string, array{int, int, int, BatteryHealth}>
     */
    public static function blockCounts(): array
    {
        return [
            'all ok' => [32, 0, 0, BatteryHealth::Normal],
            'exactly 10% failing' => [40, 2, 2, BatteryHealth::Minor],
            'just over 10%' => [32, 3, 1, BatteryHealth::Major],
            'empty bank' => [0, 0, 0, BatteryHealth::Normal],
        ];
    }

    #[Test]
    #[DataProvider('blockCounts')]
    public function battery_health_follows_the_share_of_failing_blocks(int $total, int $low, int $bad, BatteryHealth $expected): void
    {
        $this->assertSame($expected, BatteryHealth::fromBlocks($total, $low, $bad));
    }

    #[Test]
    public function every_page_renders_for_an_admin(): void
    {
        $report = MaintenanceReport::factory()->for($this->unit)->create();

        foreach (['/dashboard', '/units', '/units/create', "/units/{$this->unit->id}", "/units/{$this->unit->id}/edit",
            "/units/{$this->unit->id}/reports/create", "/reports/{$report->id}", "/reports/{$report->id}/edit", '/sites', '/staff', '/staff/create'] as $url) {
            $this->actingAs($this->admin)->get($url)->assertOk();
        }
    }

    #[Test]
    public function technicians_monitor_and_report_but_do_not_administer(): void
    {
        foreach (['/dashboard', '/units', "/units/{$this->unit->id}", "/units/{$this->unit->id}/reports/create"] as $url) {
            $this->actingAs($this->technician)->get($url)->assertOk();
        }

        foreach (['/units/create', "/units/{$this->unit->id}/edit", '/sites', '/staff'] as $url) {
            $this->actingAs($this->technician)->get($url)->assertForbidden();
        }

        $this->actingAs($this->technician)->delete("/units/{$this->unit->id}")->assertForbidden();
    }

    #[Test]
    public function the_dashboard_marks_a_silent_unit_as_not_reporting(): void
    {
        UpsReading::factory()->for($this->unit)->create(['recorded_at' => now()->subHour()]);
        $this->unit->forceFill(['last_reading_at' => now()->subHour()])->save();

        $live = UpsUnit::factory()->create();
        $live->recordReading((new SimulatedReader)->read($live));

        $this->actingAs($this->technician)->get('/dashboard')->assertInertia(fn ($page) => $page
            ->where('units.0.id', $this->unit->id)
            ->where('units.0.condition', 'not_reporting')
            ->where('units.1.condition', 'online'));
    }

    #[Test]
    public function a_report_must_count_every_block_once(): void
    {
        $this->actingAs($this->technician)->post("/units/{$this->unit->id}/reports", $this->reportPayload(['battery_blocks' => 32, 'blocks_ok' => 30, 'blocks_low' => 1, 'blocks_bad' => 0]))
            ->assertSessionHasErrors('battery_blocks');
    }

    #[Test]
    public function a_technician_files_a_report_with_an_attachment_and_downloads_it(): void
    {
        Storage::fake('local');

        $this->actingAs($this->technician)->post("/units/{$this->unit->id}/reports", $this->reportPayload([
            'file' => UploadedFile::fake()->create('report-q3.pdf', 300, 'application/pdf'),
        ]))->assertRedirect();

        $report = MaintenanceReport::query()->sole();
        $this->assertSame($this->technician->id, $report->reporter_id);
        $this->assertSame(BatteryHealth::Minor, $report->battery_health);
        Storage::disk('local')->assertExists((string) $report->file_path);

        $this->actingAs($this->technician)->get("/reports/{$report->id}/file")->assertOk()->assertDownload('report-q3.pdf');
    }

    #[Test]
    public function technicians_edit_only_their_own_reports(): void
    {
        $theirs = MaintenanceReport::factory()->for($this->unit)->create(['reporter_id' => $this->technician->id]);
        $someoneElses = MaintenanceReport::factory()->for($this->unit)->create(['reporter_id' => $this->admin->id]);

        $this->actingAs($this->technician)->get("/reports/{$theirs->id}/edit")->assertOk();
        $this->actingAs($this->technician)->get("/reports/{$someoneElses->id}/edit")->assertForbidden();
        $this->actingAs($this->technician)->delete("/reports/{$theirs->id}")->assertForbidden();
        $this->actingAs($this->admin)->get("/reports/{$someoneElses->id}/edit")->assertOk();
    }

    #[Test]
    public function poll_now_stores_a_reading_or_reports_the_failure(): void
    {
        $this->actingAs($this->technician)->post("/units/{$this->unit->id}/poll")->assertRedirect();
        $this->assertSame(1, $this->unit->readings()->count());

        Http::fake(['*' => Http::failedConnection()]);
        $card = UpsUnit::factory()->html()->create();

        $this->actingAs($this->technician)->post("/units/{$card->id}/poll")->assertRedirect();
        $this->assertSame(1, $card->fresh()?->consecutive_failures);
    }

    #[Test]
    public function an_html_unit_needs_an_endpoint(): void
    {
        $this->actingAs($this->admin)->post('/units', [
            'site_id' => $this->unit->site_id, 'name' => 'UPS B', 'serial_number' => 'SN-NEW', 'rated_power_va' => 20000,
            'phases' => 3, 'driver' => 'html', 'endpoint' => '',
        ])->assertSessionHasErrors('endpoint');
    }

    #[Test]
    public function sites_are_saved_in_the_chosen_region_and_cannot_be_removed_while_in_use(): void
    {
        $region = Region::factory()->create();

        $this->actingAs($this->admin)->post('/sites', ['region_id' => $region->id, 'code' => 'STE-900', 'name' => 'Harbour Exchange'])->assertRedirect('/sites');

        $this->assertSame($region->id, Site::query()->where('code', 'STE-900')->sole()->region_id);
        $this->actingAs($this->admin)->delete("/sites/{$this->unit->site_id}")->assertStatus(422);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function reportPayload(array $overrides = []): array
    {
        return [
            'reported_on' => now()->toDateString(),
            'room_temperature_celsius' => 24.5,
            'backup_minutes' => 45,
            'load_percent' => 38,
            'battery_voltage_positive' => 272.1,
            'battery_voltage_negative' => 271.8,
            'battery_blocks' => 32,
            'blocks_ok' => 30,
            'blocks_low' => 2,
            'blocks_bad' => 0,
            'summary' => 'Two blocks low on float voltage; scheduled for replacement.',
            ...$overrides,
        ];
    }
}
