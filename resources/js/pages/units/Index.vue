<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import UpsUnitController from '@/actions/App/Http/Controllers/UpsUnitController';
import AdminTable from '@/components/admin/AdminTable.vue';
import PageHeader from '@/components/admin/PageHeader.vue';
import Pagination from '@/components/admin/Pagination.vue';
import SearchFilter from '@/components/admin/SearchFilter.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import type { Paginated } from '@/types';

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'UPS units', href: UpsUnitController.index() }],
    },
});

type Unit = {
    id: number;
    name: string;
    serial_number: string;
    manufacturer: string | null;
    model: string | null;
    rated_power_va: number;
    phases: number;
    driver: string;
    is_active: boolean;
    site: { name: string; region: { name: string } | null } | null;
};

defineProps<{
    units: Paginated<Unit>;
    filters: { search?: string };
    canManage: boolean;
}>();

const kva = (va: number) => `${(va / 1000).toLocaleString()} kVA`;
</script>

<template>
    <Head title="UPS units" />

    <div class="flex flex-1 flex-col gap-6">
        <PageHeader
            title="UPS units"
            description="Every UPS and how it is read."
        >
            <Button v-if="canManage" as-child>
                <Link :href="UpsUnitController.create()">Add UPS</Link>
            </Button>
        </PageHeader>

        <SearchFilter
            :filters="filters"
            placeholder="Search by name or serial number"
        />

        <AdminTable :empty="units.data.length === 0">
            <template #head>
                <tr>
                    <th>Name</th>
                    <th>Site</th>
                    <th>Make and model</th>
                    <th>Serial</th>
                    <th class="text-right">Rating</th>
                    <th>Read via</th>
                </tr>
            </template>
            <tr v-for="unit in units.data" :key="unit.id">
                <td>
                    <Link
                        :href="UpsUnitController.show(unit.id)"
                        class="font-medium hover:underline"
                        >{{ unit.name }}</Link
                    >
                    <Badge
                        v-if="!unit.is_active"
                        variant="secondary"
                        class="ml-2"
                        >Paused</Badge
                    >
                </td>
                <td>
                    {{ unit.site?.name }}
                    <span class="text-xs text-muted-foreground"
                        >· {{ unit.site?.region?.name }}</span
                    >
                </td>
                <td>
                    {{
                        [unit.manufacturer, unit.model]
                            .filter(Boolean)
                            .join(' ') || '—'
                    }}
                </td>
                <td class="font-mono text-xs">{{ unit.serial_number }}</td>
                <td class="text-right tabular-nums">
                    {{ kva(unit.rated_power_va) }} · {{ unit.phases }}φ
                </td>
                <td>
                    {{ unit.driver === 'html' ? 'Network card' : 'Simulated' }}
                </td>
            </tr>
        </AdminTable>

        <Pagination :page="units" />
    </div>
</template>
