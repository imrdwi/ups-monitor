<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import MaintenanceReportController from '@/actions/App/Http/Controllers/MaintenanceReportController';
import UpsUnitController from '@/actions/App/Http/Controllers/UpsUnitController';
import DeleteButton from '@/components/admin/DeleteButton.vue';
import HealthBadge from '@/components/admin/HealthBadge.vue';
import PageHeader from '@/components/admin/PageHeader.vue';
import { Button } from '@/components/ui/button';
import { formatDate, formatDateTime } from '@/lib/format';

const props = defineProps<{
    report: {
        id: number;
        period: string;
        battery_health: string;
        reported_on: string;
        work_permit_number: string | null;
        room_temperature_celsius: number;
        backup_minutes: number;
        load_percent: number;
        battery_voltage_positive: number;
        battery_voltage_negative: number;
        battery_blocks: number;
        blocks_ok: number;
        blocks_low: number;
        blocks_bad: number;
        summary: string;
        file_name: string | null;
        updated_at: string;
        reporter: { name: string } | null;
        ups_unit: {
            id: number;
            name: string;
            site: { name: string; region: { name: string } | null } | null;
        };
    };
    canEdit: boolean;
}>();

const isAdmin = props.canEdit;

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'UPS units', href: UpsUnitController.index() },
            { title: 'Maintenance report', href: '#' },
        ],
    },
});
</script>

<template>
    <Head :title="`${report.ups_unit.name} · ${report.period}`" />

    <div class="flex flex-1 flex-col gap-6">
        <PageHeader
            :title="`${report.ups_unit.name} · ${report.period}`"
            :description="`Checked ${formatDate(report.reported_on)} by ${report.reporter?.name ?? 'unknown'}`"
        >
            <HealthBadge :health="report.battery_health" />
            <Button v-if="report.file_name" variant="outline" as-child>
                <a :href="MaintenanceReportController.download.url(report.id)"
                    >Download {{ report.file_name }}</a
                >
            </Button>
            <template v-if="canEdit">
                <Button variant="ghost" as-child>
                    <Link :href="MaintenanceReportController.edit(report.id)"
                        >Edit</Link
                    >
                </Button>
                <DeleteButton
                    v-if="isAdmin"
                    :action="
                        MaintenanceReportController.destroy.form(report.id)
                    "
                    description="Delete this maintenance report?"
                />
            </template>
        </PageHeader>

        <dl class="grid grid-cols-2 gap-4 stisla-card p-4 sm:grid-cols-4">
            <div>
                <dt class="text-xs text-muted-foreground">Room temperature</dt>
                <dd class="text-lg font-semibold tabular-nums">
                    {{ report.room_temperature_celsius }} °C
                </dd>
            </div>
            <div>
                <dt class="text-xs text-muted-foreground">Load</dt>
                <dd class="text-lg font-semibold tabular-nums">
                    {{ report.load_percent }}%
                </dd>
            </div>
            <div>
                <dt class="text-xs text-muted-foreground">Backup time</dt>
                <dd class="text-lg font-semibold tabular-nums">
                    {{ report.backup_minutes }} min
                </dd>
            </div>
            <div>
                <dt class="text-xs text-muted-foreground">Battery voltage</dt>
                <dd class="text-lg font-semibold tabular-nums">
                    +{{ report.battery_voltage_positive }} / −{{
                        report.battery_voltage_negative
                    }}
                    V
                </dd>
            </div>
            <div>
                <dt class="text-xs text-muted-foreground">Blocks OK</dt>
                <dd class="text-lg font-semibold tabular-nums">
                    {{ report.blocks_ok }} / {{ report.battery_blocks }}
                </dd>
            </div>
            <div>
                <dt class="text-xs text-muted-foreground">Low</dt>
                <dd class="text-lg font-semibold tabular-nums">
                    {{ report.blocks_low }}
                </dd>
            </div>
            <div>
                <dt class="text-xs text-muted-foreground">Bad</dt>
                <dd class="text-lg font-semibold tabular-nums">
                    {{ report.blocks_bad }}
                </dd>
            </div>
            <div>
                <dt class="text-xs text-muted-foreground">Work permit</dt>
                <dd class="font-mono text-sm">
                    {{ report.work_permit_number ?? '—' }}
                </dd>
            </div>
        </dl>

        <section>
            <h2 class="mb-2 font-medium">Summary</h2>
            <p class="text-sm whitespace-pre-line">{{ report.summary }}</p>
        </section>

        <p class="text-xs text-muted-foreground">
            {{ report.ups_unit.site?.name }} ·
            {{ report.ups_unit.site?.region?.name }} · last updated
            {{ formatDateTime(report.updated_at) }} ·
            <Link
                :href="UpsUnitController.show(report.ups_unit.id)"
                class="underline"
                >back to the UPS</Link
            >
        </p>
    </div>
</template>
