<script setup lang="ts">
import { Head, Link, router, usePoll } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import UpsUnitController from '@/actions/App/Http/Controllers/UpsUnitController';
import ConditionBadge from '@/components/admin/ConditionBadge.vue';
import HealthBadge from '@/components/admin/HealthBadge.vue';
import NativeSelect from '@/components/admin/NativeSelect.vue';
import PageHeader from '@/components/admin/PageHeader.vue';
import { formatDate, formatDateTime } from '@/lib/format';
import { dashboard } from '@/routes';

defineOptions({
    layout: { breadcrumbs: [{ title: 'Fleet status', href: dashboard() }] },
});

type Unit = {
    id: number;
    name: string;
    site: string | null;
    region: string | null;
    condition: string;
    reading: {
        load_percent: number | null;
        temperature_celsius: number | null;
        battery_capacity_percent: number | null;
    } | null;
    last_reading_at: string | null;
    battery_health: string | null;
    last_maintained_on: string | null;
};

const props = defineProps<{
    units: Unit[];
    conditions: { value: string; count: number }[];
    health: { value: string; label: string; count: number }[];
    regions: { id: number; name: string }[];
    filters: { region_id?: string };
    staleAfterMinutes: number;
}>();

// Readings arrive every minute; refresh the table on the same cadence.
usePoll(60_000, { only: ['units', 'conditions', 'health'] });

function filterRegion(event: Event) {
    const value = (event.target as HTMLSelectElement).value;
    router.get(
        dashboard().url,
        { region_id: value || undefined },
        { preserveState: true },
    );
}

const metric = (value: number | null | undefined, unit: string) =>
    value === null || value === undefined ? '—' : `${value} ${unit}`;

// The original's four tiles: total, then each battery-health band. Each one
// filters the unit cards below it. Text colour is picked per tile for 4.5:1.
const tileStyles: Record<string, string> = {
    all: 'bg-[#5a67d8] text-white',
    normal: 'bg-[#47c363] text-[#191d21]',
    minor: 'bg-[#ffa426] text-[#191d21]',
    major: 'bg-[#d93b32] text-white',
};

const tiles = computed(() => [
    { value: 'all', label: 'Total', count: props.units.length },
    ...props.health,
]);

const show = ref('all');

const shownUnits = computed(() =>
    show.value === 'all'
        ? props.units
        : props.units.filter((unit) => unit.battery_health === show.value),
);
</script>

<template>
    <Head title="Fleet status" />

    <div class="flex flex-1 flex-col gap-[30px]">
        <PageHeader
            title="Fleet status"
            :description="`Live state refreshes every minute. A unit with no reading for ${staleAfterMinutes} minutes counts as not reporting.`"
        >
            <NativeSelect
                class="w-48"
                aria-label="Filter by region"
                @change="filterRegion"
            >
                <option value="">All regions</option>
                <option
                    v-for="region in regions"
                    :key="region.id"
                    :value="region.id"
                    :selected="
                        String(region.id) ===
                        String(props.filters.region_id ?? '')
                    "
                >
                    {{ region.name }}
                </option>
            </NativeSelect>
        </PageHeader>

        <div
            class="grid gap-[30px] sm:grid-cols-2 xl:grid-cols-4"
            role="group"
            aria-label="Filter units by battery health"
        >
            <button
                v-for="tile in tiles"
                :key="tile.value"
                type="button"
                :aria-pressed="show === tile.value"
                :class="[
                    'rounded-[3px] px-[25px] pt-[25px] pb-5 text-left shadow-[0_4px_8px_rgba(0,0,0,0.03)] transition-[filter,box-shadow] hover:brightness-95 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-band',
                    tileStyles[tile.value],
                    show === tile.value && 'ring-4 ring-black/15 ring-inset',
                ]"
                @click="show = tile.value"
            >
                <span class="block text-[15px] font-semibold">{{
                    tile.label
                }}</span>
                <span
                    class="mt-1 block text-[26px] leading-[39px] font-bold tabular-nums"
                    >{{ tile.count }}</span
                >
            </button>
        </div>

        <div
            class="flex flex-wrap items-center gap-x-4 gap-y-2 stisla-card p-4 text-sm"
        >
            <span>
                Threshold: <strong class="text-ink">Normal</strong> = every
                battery block OK
            </span>
            <span aria-hidden="true">&bull;</span>
            <span>
                <strong class="text-ink">Minor</strong> = 10% of blocks or fewer
                low or bad
            </span>
            <span aria-hidden="true">&bull;</span>
            <span>
                <strong class="text-ink">Major</strong> = more than 10% low or
                bad
            </span>
        </div>

        <section class="stisla-card p-4" aria-labelledby="live-condition">
            <h2 id="live-condition" class="mb-3 text-sm font-semibold">
                Live condition
            </h2>
            <ul class="flex flex-wrap gap-x-6 gap-y-3">
                <li
                    v-for="condition in conditions"
                    :key="condition.value"
                    class="flex items-center gap-2"
                >
                    <span class="text-xl font-bold text-ink tabular-nums">{{
                        condition.count
                    }}</span>
                    <ConditionBadge :condition="condition.value" />
                </li>
            </ul>
        </section>

        <p
            v-if="shownUnits.length === 0"
            class="stisla-card p-6 text-center text-muted-foreground"
        >
            {{
                units.length ? 'No units in this band.' : 'No active UPS units.'
            }}
        </p>

        <div class="grid gap-[30px] md:grid-cols-2 xl:grid-cols-3">
            <article
                v-for="unit in shownUnits"
                :key="unit.id"
                class="stisla-card"
            >
                <header class="border-b border-[#f9f9f9] px-[25px] pt-5 pb-4">
                    <h2 class="text-base font-bold">
                        <Link
                            :href="UpsUnitController.show(unit.id)"
                            class="hover:text-band"
                            >{{ unit.name }}</Link
                        >
                    </h2>
                    <p class="text-sm">
                        {{ unit.site ?? '—' }}
                        <span v-if="unit.region">· {{ unit.region }}</span>
                    </p>
                </header>
                <dl
                    class="divide-y divide-[#f2f2f2] px-[25px] pb-2 [&_dd]:font-semibold [&_dd]:tabular-nums [&_div]:flex [&_div]:items-center [&_div]:justify-between [&_div]:gap-4 [&_div]:py-3 [&_dt]:font-semibold [&_dt]:text-ink"
                >
                    <div>
                        <dt>Condition</dt>
                        <dd><ConditionBadge :condition="unit.condition" /></dd>
                    </div>
                    <div>
                        <dt>Battery health</dt>
                        <dd class="text-right">
                            <HealthBadge :health="unit.battery_health" />
                            <span
                                v-if="unit.last_maintained_on"
                                class="block text-xs font-normal text-muted-foreground"
                                >{{ formatDate(unit.last_maintained_on) }}</span
                            >
                        </dd>
                    </div>
                    <div>
                        <dt>Load</dt>
                        <dd>{{ metric(unit.reading?.load_percent, '%') }}</dd>
                    </div>
                    <div>
                        <dt>Temperature</dt>
                        <dd>
                            {{
                                metric(unit.reading?.temperature_celsius, '°C')
                            }}
                        </dd>
                    </div>
                    <div>
                        <dt>Battery</dt>
                        <dd>
                            {{
                                metric(
                                    unit.reading?.battery_capacity_percent,
                                    '%',
                                )
                            }}
                        </dd>
                    </div>
                    <div>
                        <dt>Last reading</dt>
                        <dd
                            :class="{
                                'text-destructive':
                                    unit.condition === 'not_reporting',
                            }"
                        >
                            {{ formatDateTime(unit.last_reading_at) }}
                        </dd>
                    </div>
                </dl>
            </article>
        </div>
    </div>
</template>
