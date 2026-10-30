<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import { ref } from 'vue';
import UpsUnitController from '@/actions/App/Http/Controllers/UpsUnitController';
import FormField from '@/components/admin/FormField.vue';
import NativeSelect from '@/components/admin/NativeSelect.vue';
import PageHeader from '@/components/admin/PageHeader.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { toDateInput } from '@/lib/format';

type Unit = {
    id: number;
    site_id: number;
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
};

const props = defineProps<{
    unit: Unit | null;
    sites: { id: number; label: string }[];
}>();

const endpointExample = 'http://ups-card.local/status.htm';
const driver = ref(props.unit?.driver ?? 'html');

function setDriver(event: Event) {
    driver.value = (event.target as HTMLSelectElement).value;
}
const title = props.unit ? `Edit ${props.unit.name}` : 'Add UPS';

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'UPS units', href: UpsUnitController.index() },
            { title: 'Form', href: '#' },
        ],
    },
});
</script>

<template>
    <Head :title="title" />

    <div class="flex flex-1 flex-col gap-6">
        <PageHeader :title="title" />

        <Form
            v-bind="
                unit
                    ? UpsUnitController.update.form(unit.id)
                    : UpsUnitController.store.form()
            "
            class="max-w-4xl space-y-6 stisla-card p-[25px]"
            v-slot="{ errors, processing }"
        >
            <div class="grid gap-5 sm:grid-cols-2">
                <FormField label="Name" for="name" :error="errors.name">
                    <Input
                        id="name"
                        name="name"
                        :default-value="unit?.name"
                        required
                    />
                </FormField>
                <FormField label="Site" for="site_id" :error="errors.site_id">
                    <NativeSelect id="site_id" name="site_id" required>
                        <option value="" disabled :selected="!unit">
                            Choose a site
                        </option>
                        <option
                            v-for="site in sites"
                            :key="site.id"
                            :value="site.id"
                            :selected="site.id === unit?.site_id"
                        >
                            {{ site.label }}
                        </option>
                    </NativeSelect>
                </FormField>
                <FormField
                    label="Manufacturer"
                    for="manufacturer"
                    :error="errors.manufacturer"
                >
                    <Input
                        id="manufacturer"
                        name="manufacturer"
                        :default-value="unit?.manufacturer ?? ''"
                    />
                </FormField>
                <FormField label="Model" for="model" :error="errors.model">
                    <Input
                        id="model"
                        name="model"
                        :default-value="unit?.model ?? ''"
                    />
                </FormField>
                <FormField
                    label="Serial number"
                    for="serial_number"
                    :error="errors.serial_number"
                >
                    <Input
                        id="serial_number"
                        name="serial_number"
                        class="font-mono"
                        :default-value="unit?.serial_number"
                        required
                    />
                </FormField>
                <FormField
                    label="Rated power (VA)"
                    for="rated_power_va"
                    :error="errors.rated_power_va"
                    hint="Used to work out load when the card does not report it."
                >
                    <Input
                        id="rated_power_va"
                        name="rated_power_va"
                        type="number"
                        min="100"
                        :default-value="unit?.rated_power_va"
                        required
                    />
                </FormField>
                <FormField label="Phases" for="phases" :error="errors.phases">
                    <NativeSelect id="phases" name="phases">
                        <option value="1" :selected="unit?.phases !== 3">
                            Single phase
                        </option>
                        <option value="3" :selected="unit?.phases === 3">
                            Three phase
                        </option>
                    </NativeSelect>
                </FormField>
                <FormField
                    label="Installed on"
                    for="installed_on"
                    :error="errors.installed_on"
                >
                    <Input
                        id="installed_on"
                        name="installed_on"
                        type="date"
                        :default-value="toDateInput(unit?.installed_on)"
                    />
                </FormField>
                <input type="hidden" name="is_modular" value="0" />
                <label class="flex items-center gap-2 text-sm sm:col-span-2">
                    <input
                        type="checkbox"
                        name="is_modular"
                        value="1"
                        class="size-4 accent-primary"
                        :checked="unit?.is_modular"
                    />
                    Modular UPS
                </label>
            </div>

            <fieldset class="grid gap-5 rounded-lg border p-4 sm:grid-cols-3">
                <legend class="px-1 text-sm font-medium">Battery bank</legend>
                <FormField
                    label="Brand"
                    for="battery_brand"
                    :error="errors.battery_brand"
                >
                    <Input
                        id="battery_brand"
                        name="battery_brand"
                        :default-value="unit?.battery_brand ?? ''"
                    />
                </FormField>
                <FormField
                    label="Type"
                    for="battery_type"
                    :error="errors.battery_type"
                >
                    <Input
                        id="battery_type"
                        name="battery_type"
                        :default-value="unit?.battery_type ?? ''"
                        placeholder="VRLA 12V"
                    />
                </FormField>
                <FormField
                    label="Capacity"
                    for="battery_capacity"
                    :error="errors.battery_capacity"
                >
                    <Input
                        id="battery_capacity"
                        name="battery_capacity"
                        :default-value="unit?.battery_capacity ?? ''"
                        placeholder="100 Ah"
                    />
                </FormField>
            </fieldset>

            <fieldset class="grid gap-5 rounded-lg border p-4 sm:grid-cols-2">
                <legend class="px-1 text-sm font-medium">Monitoring</legend>
                <FormField
                    label="Read readings from"
                    for="driver"
                    :error="errors.driver"
                >
                    <NativeSelect id="driver" name="driver" @change="setDriver">
                        <option value="html" :selected="driver === 'html'">
                            Network card status page
                        </option>
                        <option
                            value="simulated"
                            :selected="driver === 'simulated'"
                        >
                            Simulated (demo)
                        </option>
                    </NativeSelect>
                </FormField>
                <FormField
                    v-if="driver === 'html'"
                    label="Status page URL"
                    for="endpoint"
                    :error="errors.endpoint"
                >
                    <Input
                        id="endpoint"
                        name="endpoint"
                        type="url"
                        class="font-mono"
                        :default-value="unit?.endpoint ?? ''"
                        :placeholder="endpointExample"
                        required
                    />
                </FormField>
                <input type="hidden" name="is_active" value="0" />
                <label class="flex items-center gap-2 text-sm sm:col-span-2">
                    <input
                        type="checkbox"
                        name="is_active"
                        value="1"
                        class="size-4 accent-primary"
                        :checked="unit?.is_active ?? true"
                    />
                    Poll every minute
                </label>
            </fieldset>

            <div class="flex gap-2">
                <Button :disabled="processing">Save</Button>
                <Button variant="ghost" as-child>
                    <Link
                        :href="
                            unit
                                ? UpsUnitController.show(unit.id)
                                : UpsUnitController.index()
                        "
                        >Cancel</Link
                    >
                </Button>
            </div>
        </Form>
    </div>
</template>
