<?php

namespace App\Readers;

use App\Models\UpsUnit;
use App\UpsStatus;
use Dom\HTMLDocument;
use Illuminate\Support\Facades\Http;

/**
 * Reads the status page served by a UPS network management card.
 *
 * The page is a table of label cells, each followed by a value cell.
 * Three-phase units report one value per phase, e.g. "221/ 220/ 219".
 */
class HtmlStatusPageReader implements UpsReader
{
    /**
     * Value labels, matched case-insensitively against the start of a label cell.
     *
     * @var array<string, list<string>>
     */
    private const LABELS = [
        'status' => ['UPS Status'],
        'input_voltage' => ['Input Voltage', 'Rectifier Voltage'],
        'output_voltage' => ['Output Voltage'],
        'output_current' => ['Output Current'],
        'load_percent' => ['Output Load', 'Load'],
        'frequency_hz' => ['Output Frequency', 'Frequency'],
        'temperature_celsius' => ['Temperature'],
        'battery_capacity_percent' => ['Battery Capacity'],
        'battery_voltage' => ['Battery Voltage'],
        'battery_runtime' => ['Battery Time', 'Battery Runtime', 'Runtime'],
    ];

    public function read(UpsUnit $unit): ReadingData
    {
        if (blank($unit->endpoint)) {
            throw new UnreadableStatusPage("UPS {$unit->serial_number} has no endpoint configured.");
        }

        $response = Http::timeout((int) config('ups.timeout_seconds'))->get($unit->endpoint);

        // Some cards serve a complete status page with an error status code,
        // so the body decides whether the poll worked, not the status.
        if (! str_contains(strtolower($response->body()), '<td')) {
            throw new UnreadableStatusPage("UPS {$unit->serial_number} returned HTTP {$response->status()} without a status table.");
        }

        return $this->parse($response->body(), $unit);
    }

    public function parse(string $html, UpsUnit $unit): ReadingData
    {
        $values = $this->extractValues($html);

        if (! isset($values['status'])) {
            throw new UnreadableStatusPage("UPS {$unit->serial_number} status page has no UPS Status field.");
        }

        $outputVoltages = $this->numbers($values['output_voltage'] ?? null);
        $outputCurrents = $this->numbers($values['output_current'] ?? null);

        return new ReadingData(
            status: UpsStatus::fromDeviceText($values['status']),
            recordedAt: now()->toImmutable(),
            temperatureCelsius: $this->first($values['temperature_celsius'] ?? null),
            loadPercent: $this->loadPercent($values['load_percent'] ?? null, $outputVoltages, $outputCurrents, $unit),
            batteryCapacityPercent: $this->first($values['battery_capacity_percent'] ?? null),
            batteryVoltage: $this->first($values['battery_voltage'] ?? null),
            batteryRuntimeMinutes: $this->minutes($values['battery_runtime'] ?? null),
            inputVoltage: $this->average($this->numbers($values['input_voltage'] ?? null)),
            outputVoltage: $this->average($outputVoltages),
            outputCurrent: $this->average($outputCurrents),
            frequencyHz: $this->first($values['frequency_hz'] ?? null),
            raw: $values,
        );
    }

    /**
     * @return array<string, string>
     */
    private function extractValues(string $html): array
    {
        $document = HTMLDocument::createFromString($html, LIBXML_NOERROR);
        $values = [];

        foreach ($document->querySelectorAll('td, th') as $cell) {
            $label = $this->clean($cell->textContent);
            $valueCell = $cell->nextElementSibling;

            if ($label === '' || $valueCell === null) {
                continue;
            }

            foreach (self::LABELS as $key => $prefixes) {
                if (isset($values[$key])) {
                    continue;
                }

                foreach ($prefixes as $prefix) {
                    if (str_starts_with(strtolower($label), strtolower($prefix))) {
                        $values[$key] = $this->clean($valueCell->textContent);

                        continue 3;
                    }
                }
            }
        }

        return $values;
    }

    /**
     * Use the card's own load figure when it shows one (worst phase wins);
     * otherwise derive it from output power against the rated capacity.
     *
     * @param  list<float>  $voltages
     * @param  list<float>  $currents
     */
    private function loadPercent(?string $reported, array $voltages, array $currents, UpsUnit $unit): ?float
    {
        $reportedPhases = $this->numbers($reported);

        if ($reportedPhases !== []) {
            return max($reportedPhases);
        }

        if ($voltages === [] || count($voltages) !== count($currents) || $unit->rated_power_va <= 0) {
            return null;
        }

        $apparentPower = array_sum(array_map(fn (float $volts, float $amps): float => $volts * $amps, $voltages, $currents));

        return round($apparentPower / $unit->rated_power_va * 100, 2);
    }

    /**
     * @return list<float>
     */
    private function numbers(?string $value): array
    {
        if ($value === null) {
            return [];
        }

        preg_match_all('/-?\d+(?:[.,]\d+)?/', $value, $matches);

        return array_map(fn (string $number): float => (float) str_replace(',', '.', $number), $matches[0]);
    }

    private function first(?string $value): ?float
    {
        return $this->numbers($value)[0] ?? null;
    }

    /**
     * @param  list<float>  $values
     */
    private function average(array $values): ?float
    {
        return $values === [] ? null : round(array_sum($values) / count($values), 2);
    }

    /**
     * Accepts "45 min", "45", or "1:05" (hours and minutes).
     */
    private function minutes(?string $value): ?int
    {
        if ($value === null) {
            return null;
        }

        if (preg_match('/(\d+):(\d{2})/', $value, $time)) {
            return (int) $time[1] * 60 + (int) $time[2];
        }

        $number = $this->first($value);

        return $number === null ? null : (int) round($number);
    }

    private function clean(string $text): string
    {
        return trim(preg_replace('/\s+/u', ' ', html_entity_decode($text)) ?? '', " \t\n\r\0\x0B\u{A0}");
    }
}
