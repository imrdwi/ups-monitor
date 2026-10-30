<script setup lang="ts">
import { Form, Head, Link, usePoll } from '@inertiajs/vue3';
import { computed } from 'vue';
import MaintenanceReportController from '@/actions/App/Http/Controllers/MaintenanceReportController';
import UpsUnitController from '@/actions/App/Http/Controllers/UpsUnitController';
import AdminTable from '@/components/admin/AdminTable.vue';
import ConditionBadge from '@/components/admin/ConditionBadge.vue';
import DeleteButton from '@/components/admin/DeleteButton.vue';
import HealthBadge from '@/components/admin/HealthBadge.vue';
import LineChart from '@/components/admin/LineChart.vue';
import PageHeader from '@/components/admin/PageHeader.vue';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import { formatDate, formatDateTime } from '@/lib/format';

type Reading = {
    status: string;
    temperature_celsius: number | null;
    load_percent: number | null;
    battery_capacity_percent: number | null;
    battery_voltage: number | null;
    battery_runtime_minutes: number | null;
    input_voltage: number | null;
    output_voltage: number | null;
    output_current: number | null;
    frequency_hz: number | null;
    recorded_at: string;
};

const props = defineProps<{
    unit: {
        id: number;
        name: string;
        serial_number: string;
        manufacturer: string | null;
        model: string | null;
        rated_power_va: number;
        phases: number;
        is_modular: boolean;
        battery_brand: string | null;
        battery_type: string | null;
        battery_capacity: string | null;
        installed_on: string | null;
        driver: string;
        endpoint: string | null;
        is_active: boolean;
        last_polled_at: string | null;
        consecutive_failures: number;
        last_error: string | null;
        site: {
            name: string;
            code: string;
            region: { name: string } | null;
        } | null;
        latest_reading: Reading | null;
    };
    stale: boolean;
    history: { at: string; load: number | null; temperature: number | null }[];
    reports: {
        id: number;
        reported_on: string;
        period: string;
        battery_health: string;
        backup_minutes: number;
        load_percent: number;
        blocks_ok: number;
        battery_blocks: number;
        reporter: { name: string } | null;
    }[];
    canManage: boolean;
}>();

usePoll(60_000, { only: ['unit', 'stale', 'history'] });

const reading = computed(() => props.unit.latest_reading);
const condition = computed(() =>
    props.stale ? 'not_reporting' : (reading.value?.status ?? 'unknown'),
);
const loadPoints = computed(() =>
    props.history.map((point) => ({ at: point.at, value: point.load })),
);
const temperaturePoints = computed(() =>
    props.history.map((point) => ({ at: point.at, value: point.temperature })),
);

const live = computed(() =>
    reading.value
        ? [
              ['Load', reading.value.load_percent, '%'],
              ['Temperature', reading.value.temperature_celsius, '°C'],
              ['Battery charge', reading.value.battery_capacity_percent, '%'],
              ['Battery runtime', reading.value.battery_runtime_minutes, 'min'],
              ['Battery voltage', reading.value.battery_voltage, 'V'],
              ['Input voltage', reading.value.input_voltage, 'V'],
              ['Output voltage', reading.value.output_voltage, 'V'],
              ['Output current', reading.value.output_current, 'A'],
              ['Frequency', reading.value.frequency_hz, 'Hz'],
          ]
        : [],
);

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'UPS units', href: UpsUnitController.index() },
            { title: 'Detail', href: '#' },
        ],
    },
});
</script>

<template>
    <Head :title="unit.name" />

    <div class="flex flex-1 flex-col gap-6">
        <PageHeader
            :title="unit.name"
            :description="`${unit.site?.name ?? ''} · ${unit.site?.region?.name ?? ''}`"
        >
            <ConditionBadge :condition="condition" />
            <Form
                v-bind="UpsUnitController.poll.form(unit.id)"
                v-slot="{ processing }"
            >
                <Button variant="outline" :disabled="processing">{{
                    processing ? 'Polling…' : 'Poll now'
                }}</Button>
            </Form>
            <Button as-child>
                <Link :href="MaintenanceReportController.create(unit.id)"
                    >New maintenance report</Link
                >
            </Button>
            <template v-if="canManage">
                <Button variant="ghost" as-child>
                    <Link :href="UpsUnitController.edit(unit.id)">Edit</Link>
                </Button>
                <DeleteButton
                    :action="UpsUnitController.destroy.form(unit.id)"
                    label="Remove"
                    :description="`Remove ${unit.name} with all its readings and maintenance reports?`"
                />
            </template>
        </PageHeader>

        <Alert v-if="unit.consecutive_failures > 0" variant="destructive">
            <AlertTitle
                >The last {{ unit.consecutive_failures }} poll(s)
                failed</AlertTitle
            >
            <AlertDescription class="font-mono text-xs break-all">{{
                unit.last_error
            }}</AlertDescription>
        </Alert>

        <div class="grid gap-4 lg:grid-cols-3">
            <section class="stisla-card p-4 lg:col-span-2">
                <div class="mb-3 flex items-baseline justify-between gap-4">
                    <h2 class="font-medium">Live reading</h2>
                    <span class="text-xs text-muted-foreground">{{
                        reading
                            ? formatDateTime(reading.recorded_at)
                            : 'No readings yet'
                    }}</span>
                </div>
                <dl class="grid grid-cols-2 gap-4 sm:grid-cols-3">
                    <div
                        v-for="[label, value, suffix] in live"
                        :key="String(label)"
                    >
                        <dt class="text-xs text-muted-foreground">
                            {{ label }}
                        </dt>
                        <dd class="text-lg font-semibold tabular-nums">
                            {{ value ?? '—'
                            }}<span
                                v-if="value !== null"
                                class="ml-1 text-sm font-normal text-muted-foreground"
                                >{{ suffix }}</span
                            >
                        </dd>
                    </div>
                </dl>
            </section>

            <section class="stisla-card p-4">
                <h2 class="mb-3 font-medium">Asset</h2>
                <dl class="grid grid-cols-[auto_1fr] gap-x-4 gap-y-1.5 text-sm">
                    <dt class="text-muted-foreground">Make</dt>
                    <dd>
                        {{
                            [unit.manufacturer, unit.model]
                                .filter(Boolean)
                                .join(' ') || '—'
                        }}
                    </dd>
                    <dt class="text-muted-foreground">Serial</dt>
                    <dd class="font-mono text-xs">{{ unit.serial_number }}</dd>
                    <dt class="text-muted-foreground">Rating</dt>
                    <dd>
                        {{ (unit.rated_power_va / 1000).toLocaleString() }} kVA
                        · {{ unit.phases }}-phase{{
                            unit.is_modular ? ' · modular' : ''
                        }}
                    </dd>
                    <dt class="text-muted-foreground">Battery</dt>
                    <dd>
                        {{
                            [
                                unit.battery_brand,
                                unit.battery_type,
                                unit.battery_capacity,
                            ]
                                .filter(Boolean)
                                .join(' · ') || '—'
                        }}
                    </dd>
                    <dt class="text-muted-foreground">Installed</dt>
                    <dd>{{ formatDate(unit.installed_on) }}</dd>
                    <dt class="text-muted-foreground">Read via</dt>
                    <dd class="break-all">
                        {{
                            unit.driver === 'html'
                                ? unit.endpoint
                                : 'Simulated data'
                        }}
                    </dd>
                </dl>
            </section>
        </div>

        <section class="grid gap-6 stisla-card p-4 lg:grid-cols-2">
            <LineChart
                title="Load, last 24 hours"
                unit="%"
                :points="loadPoints"
                :minimum="0"
                :maximum="100"
            />
            <LineChart
                title="Temperature, last 24 hours"
                unit="°C"
                :points="temperaturePoints"
            />
        </section>

        <section class="space-y-3">
            <h2 class="font-medium">Maintenance history</h2>
            <AdminTable
                :empty="reports.length === 0"
                empty-text="No maintenance checks recorded yet."
            >
                <template #head>
                    <tr>
                        <th>Date</th>
                        <th>Period</th>
                        <th>Battery health</th>
                        <th class="text-right">Blocks OK</th>
                        <th class="text-right">Backup</th>
                        <th class="text-right">Load</th>
                        <th>Technician</th>
                    </tr>
                </template>
                <tr v-for="report in reports" :key="report.id">
                    <td>
                        <Link
                            :href="MaintenanceReportController.show(report.id)"
                            class="font-medium hover:underline"
                            >{{ formatDate(report.reported_on) }}</Link
                        >
                    </td>
                    <td>{{ report.period }}</td>
                    <td><HealthBadge :health="report.battery_health" /></td>
                    <td class="text-right tabular-nums">
                        {{ report.blocks_ok }} / {{ report.battery_blocks }}
                    </td>
                    <td class="text-right tabular-nums">
                        {{ report.backup_minutes }} min
                    </td>
                    <td class="text-right tabular-nums">
                        {{ report.load_percent }}%
                    </td>
                    <td>{{ report.reporter?.name ?? '—' }}</td>
                </tr>
            </AdminTable>
        </section>
    </div>
</template>
