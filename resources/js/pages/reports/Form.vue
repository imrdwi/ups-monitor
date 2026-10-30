<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import MaintenanceReportController from '@/actions/App/Http/Controllers/MaintenanceReportController';
import UpsUnitController from '@/actions/App/Http/Controllers/UpsUnitController';
import FormField from '@/components/admin/FormField.vue';
import HealthBadge from '@/components/admin/HealthBadge.vue';
import PageHeader from '@/components/admin/PageHeader.vue';
import Textarea from '@/components/admin/Textarea.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { toDateInput } from '@/lib/format';

type Report = {
    id: number;
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
};

const props = defineProps<{
    unit: { id: number; name: string };
    report: Report | null;
    suggested: {
        load_percent: number | null;
        temperature_celsius: number | null;
    } | null;
}>();

const blocks = ref({
    total: props.report?.battery_blocks ?? 0,
    low: props.report?.blocks_low ?? 0,
    bad: props.report?.blocks_bad ?? 0,
});
const ok = computed(() =>
    Math.max(0, blocks.value.total - blocks.value.low - blocks.value.bad),
);
const health = computed(() => {
    const failing = blocks.value.low + blocks.value.bad;

    if (!blocks.value.total || failing === 0) {
        return 'normal';
    }

    return failing / blocks.value.total <= 0.1 ? 'minor' : 'major';
});

const today = new Date().toISOString().slice(0, 10);
const title = props.report
    ? 'Edit maintenance report'
    : `Maintenance check: ${props.unit.name}`;

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
    <Head :title="title" />

    <div class="flex flex-1 flex-col gap-6">
        <PageHeader
            :title="title"
            description="Record what you measured on site."
        />

        <Form
            v-bind="
                report
                    ? MaintenanceReportController.update.form(report.id)
                    : MaintenanceReportController.store.form(unit.id)
            "
            class="max-w-4xl space-y-6 stisla-card p-[25px]"
            v-slot="{ errors, processing, progress }"
        >
            <div class="grid gap-5 sm:grid-cols-2">
                <FormField
                    label="Date of check"
                    for="reported_on"
                    :error="errors.reported_on"
                >
                    <Input
                        id="reported_on"
                        name="reported_on"
                        type="date"
                        :max="today"
                        :default-value="
                            toDateInput(report?.reported_on) || today
                        "
                        required
                    />
                </FormField>
                <FormField
                    label="Work permit number"
                    for="work_permit_number"
                    :error="errors.work_permit_number"
                >
                    <Input
                        id="work_permit_number"
                        name="work_permit_number"
                        class="font-mono"
                        :default-value="report?.work_permit_number ?? ''"
                    />
                </FormField>
                <FormField
                    label="Room temperature (°C)"
                    for="room_temperature_celsius"
                    :error="errors.room_temperature_celsius"
                >
                    <Input
                        id="room_temperature_celsius"
                        name="room_temperature_celsius"
                        type="number"
                        step="0.1"
                        :default-value="
                            report?.room_temperature_celsius ??
                            suggested?.temperature_celsius ??
                            ''
                        "
                        required
                    />
                </FormField>
                <FormField
                    label="Load (%)"
                    for="load_percent"
                    :error="errors.load_percent"
                    :hint="
                        suggested?.load_percent
                            ? `Live reading: ${suggested.load_percent}%`
                            : undefined
                    "
                >
                    <Input
                        id="load_percent"
                        name="load_percent"
                        type="number"
                        step="0.1"
                        :default-value="
                            report?.load_percent ??
                            suggested?.load_percent ??
                            ''
                        "
                        required
                    />
                </FormField>
                <FormField
                    label="Backup time (minutes)"
                    for="backup_minutes"
                    :error="errors.backup_minutes"
                >
                    <Input
                        id="backup_minutes"
                        name="backup_minutes"
                        type="number"
                        min="0"
                        :default-value="report?.backup_minutes"
                        required
                    />
                </FormField>
            </div>

            <fieldset class="space-y-4 rounded-lg border p-4">
                <legend class="px-1 text-sm font-medium">Battery bank</legend>
                <div class="grid gap-5 sm:grid-cols-2">
                    <FormField
                        label="Voltage + (V)"
                        for="battery_voltage_positive"
                        :error="errors.battery_voltage_positive"
                    >
                        <Input
                            id="battery_voltage_positive"
                            name="battery_voltage_positive"
                            type="number"
                            step="0.1"
                            :default-value="report?.battery_voltage_positive"
                            required
                        />
                    </FormField>
                    <FormField
                        label="Voltage − (V)"
                        for="battery_voltage_negative"
                        :error="errors.battery_voltage_negative"
                    >
                        <Input
                            id="battery_voltage_negative"
                            name="battery_voltage_negative"
                            type="number"
                            step="0.1"
                            :default-value="report?.battery_voltage_negative"
                            required
                        />
                    </FormField>
                </div>
                <div class="grid gap-5 sm:grid-cols-4">
                    <FormField
                        label="Blocks in bank"
                        for="battery_blocks"
                        :error="errors.battery_blocks"
                    >
                        <Input
                            id="battery_blocks"
                            v-model.number="blocks.total"
                            name="battery_blocks"
                            type="number"
                            min="1"
                            required
                        />
                    </FormField>
                    <FormField
                        label="Low"
                        for="blocks_low"
                        :error="errors.blocks_low"
                    >
                        <Input
                            id="blocks_low"
                            v-model.number="blocks.low"
                            name="blocks_low"
                            type="number"
                            min="0"
                            required
                        />
                    </FormField>
                    <FormField
                        label="Bad"
                        for="blocks_bad"
                        :error="errors.blocks_bad"
                    >
                        <Input
                            id="blocks_bad"
                            v-model.number="blocks.bad"
                            name="blocks_bad"
                            type="number"
                            min="0"
                            required
                        />
                    </FormField>
                    <FormField
                        label="OK"
                        for="blocks_ok"
                        :error="errors.blocks_ok"
                    >
                        <Input
                            id="blocks_ok"
                            :model-value="ok"
                            name="blocks_ok"
                            type="number"
                            readonly
                            class="bg-muted"
                        />
                    </FormField>
                </div>
                <p class="flex items-center gap-2 text-sm">
                    Battery health: <HealthBadge :health="health" />
                </p>
            </fieldset>

            <FormField
                label="Summary"
                for="summary"
                :error="errors.summary"
                hint="Findings, work done and recommendations."
            >
                <Textarea
                    id="summary"
                    name="summary"
                    class="min-h-32"
                    :value="report?.summary ?? ''"
                    required
                />
            </FormField>

            <FormField
                label="Attach the signed report"
                for="file"
                :error="errors.file"
                :hint="
                    report?.file_name
                        ? `Current file: ${report.file_name}. Choosing a new one replaces it.`
                        : 'PDF, image, Excel or Word, up to 10 MB.'
                "
            >
                <Input
                    id="file"
                    name="file"
                    type="file"
                    accept=".pdf,.jpg,.jpeg,.png,.xlsx,.docx"
                />
                <progress
                    v-if="progress"
                    :value="progress.percentage"
                    max="100"
                    class="w-full"
                />
            </FormField>

            <div class="flex gap-2">
                <Button :disabled="processing">Save report</Button>
                <Button variant="ghost" as-child>
                    <Link
                        :href="
                            report
                                ? MaintenanceReportController.show(report.id)
                                : UpsUnitController.show(unit.id)
                        "
                        >Cancel</Link
                    >
                </Button>
            </div>
        </Form>
    </div>
</template>
