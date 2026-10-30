<script setup lang="ts">
import { computed, ref } from 'vue';
import { formatDateTime, timeZone } from '@/lib/format';

/**
 * One series over time on its own axis. The title names the series, so there is no legend;
 * a crosshair reads out the nearest point, and the same data is available as a table.
 */
const props = withDefaults(
    defineProps<{
        title: string;
        unit: string;
        points: { at: string; value: number | null }[];
        minimum?: number;
        maximum?: number;
    }>(),
    { minimum: undefined, maximum: undefined },
);

const width = 640;
const height = 200;
const margin = { top: 12, right: 12, bottom: 24, left: 40 };
const plotWidth = width - margin.left - margin.right;
const plotHeight = height - margin.top - margin.bottom;

const values = computed(
    () =>
        props.points.filter((point) => point.value !== null) as {
            at: string;
            value: number;
        }[],
);
const times = computed(() =>
    values.value.map((point) => new Date(point.at).getTime()),
);

const domain = computed(() => {
    const numbers = values.value.map((point) => point.value);
    let low = props.minimum ?? Math.min(...numbers);
    let high = props.maximum ?? Math.max(...numbers);

    if (!numbers.length) {
        return { low: 0, high: 1 };
    }

    if (low === high) {
        low -= 1;
        high += 1;
    }

    const pad = (high - low) * 0.1;

    return {
        low: props.minimum ?? Math.floor(low - pad),
        high: props.maximum ?? Math.ceil(high + pad),
    };
});

const timeDomain = computed(() => ({
    start: Math.min(...times.value),
    end: Math.max(...times.value),
}));

const x = (time: number) =>
    margin.left +
    ((time - timeDomain.value.start) /
        Math.max(timeDomain.value.end - timeDomain.value.start, 1)) *
        plotWidth;
const y = (value: number) =>
    margin.top +
    (1 - (value - domain.value.low) / (domain.value.high - domain.value.low)) *
        plotHeight;

const path = computed(() =>
    values.value
        .map(
            (point, index) =>
                `${index ? 'L' : 'M'}${x(times.value[index]).toFixed(1)},${y(point.value).toFixed(1)}`,
        )
        .join(''),
);

const gridValues = computed(() => {
    const { low, high } = domain.value;

    return [low, low + (high - low) / 2, high].map(
        (value) => Math.round(value * 10) / 10,
    );
});

const timeTicks = computed(() => {
    if (!times.value.length) {
        return [];
    }

    const { start, end } = timeDomain.value;

    return [0, 0.25, 0.5, 0.75, 1].map(
        (share) => start + (end - start) * share,
    );
});

const hourFormat = new Intl.DateTimeFormat(undefined, {
    hour: '2-digit',
    minute: '2-digit',
    hourCycle: 'h23',
    timeZone,
});

const tickAnchor = (index: number) =>
    index === 0
        ? 'start'
        : index === timeTicks.value.length - 1
          ? 'end'
          : 'middle';

const active = ref<number | null>(null);
const showTable = ref(false);

function nearest(clientX: number, target: SVGSVGElement) {
    const box = target.getBoundingClientRect();
    const position = ((clientX - box.left) / box.width) * width;
    let best = 0;

    times.value.forEach((time, index) => {
        if (
            Math.abs(x(time) - position) <
            Math.abs(x(times.value[best]) - position)
        ) {
            best = index;
        }
    });

    active.value = best;
}

function step(delta: number) {
    if (!values.value.length) {
        return;
    }

    active.value = Math.min(
        values.value.length - 1,
        Math.max(0, (active.value ?? values.value.length - 1) + delta),
    );
}

const latest = computed(() => values.value.at(-1));
const activePoint = computed(() =>
    active.value === null ? null : values.value[active.value],
);
</script>

<template>
    <figure class="viz-root space-y-2">
        <figcaption class="flex items-baseline justify-between gap-4">
            <span class="text-sm font-medium">{{ title }}</span>
            <span v-if="latest" class="text-sm text-muted-foreground">
                now
                <strong class="font-semibold text-foreground tabular-nums"
                    >{{ latest.value }} {{ unit }}</strong
                >
            </span>
        </figcaption>

        <p
            v-if="!values.length"
            class="rounded-lg border p-6 text-center text-sm text-muted-foreground"
        >
            No readings in this period.
        </p>

        <div v-else class="relative">
            <svg
                :viewBox="`0 0 ${width} ${height}`"
                class="w-full touch-none outline-none focus-visible:ring-2 focus-visible:ring-ring"
                role="img"
                tabindex="0"
                :aria-label="`${title}, ${values.length} readings. Use the arrow keys to read values.`"
                @pointermove="
                    nearest(
                        $event.clientX,
                        $event.currentTarget as SVGSVGElement,
                    )
                "
                @pointerleave="active = null"
                @keydown.left.prevent="step(-1)"
                @keydown.right.prevent="step(1)"
                @blur="active = null"
            >
                <g class="text-[11px]" fill="currentColor">
                    <template v-for="value in gridValues" :key="value">
                        <line
                            :x1="margin.left"
                            :x2="width - margin.right"
                            :y1="y(value)"
                            :y2="y(value)"
                            class="stroke-border"
                            stroke-width="1"
                        />
                        <text
                            :x="margin.left - 6"
                            :y="y(value) + 3"
                            text-anchor="end"
                            class="fill-muted-foreground"
                        >
                            {{ value }}
                        </text>
                    </template>
                    <text
                        v-for="(tick, index) in timeTicks"
                        :key="tick"
                        :x="x(tick)"
                        :y="height - 6"
                        :text-anchor="tickAnchor(index)"
                        class="fill-muted-foreground"
                    >
                        {{ hourFormat.format(new Date(tick)) }}
                    </text>
                </g>
                <path
                    :d="path"
                    fill="none"
                    stroke="var(--series-1)"
                    stroke-width="2"
                    stroke-linejoin="round"
                    stroke-linecap="round"
                />
                <template v-if="activePoint && active !== null">
                    <line
                        :x1="x(times[active])"
                        :x2="x(times[active])"
                        :y1="margin.top"
                        :y2="height - margin.bottom"
                        class="stroke-muted-foreground"
                        stroke-width="1"
                    />
                    <circle
                        :cx="x(times[active])"
                        :cy="y(activePoint.value)"
                        r="4"
                        fill="var(--series-1)"
                        class="stroke-background"
                        stroke-width="2"
                    />
                </template>
            </svg>
            <div
                v-if="activePoint && active !== null"
                class="pointer-events-none absolute top-1 rounded-md border bg-popover px-2 py-1 text-xs shadow-sm"
                :style="{
                    left: `${(x(times[active]) / width) * 100}%`,
                    transform:
                        x(times[active]) > width / 2
                            ? 'translateX(calc(-100% - 8px))'
                            : 'translateX(8px)',
                }"
                role="status"
            >
                <strong class="block text-sm tabular-nums"
                    >{{ activePoint.value }} {{ unit }}</strong
                >
                <span class="text-muted-foreground">{{
                    formatDateTime(activePoint.at)
                }}</span>
            </div>
        </div>

        <button
            v-if="values.length"
            type="button"
            class="text-xs text-muted-foreground underline"
            @click="showTable = !showTable"
        >
            {{ showTable ? 'Hide' : 'Show' }} as table
        </button>
        <div
            v-if="showTable"
            class="max-h-56 overflow-y-auto rounded-md border text-xs"
        >
            <table class="w-full">
                <thead class="sticky top-0 bg-muted">
                    <tr>
                        <th class="px-2 py-1 text-left font-medium">Time</th>
                        <th class="px-2 py-1 text-right font-medium">
                            {{ title }} ({{ unit }})
                        </th>
                    </tr>
                </thead>
                <tbody>
                    <tr
                        v-for="point in [...values].reverse()"
                        :key="point.at"
                        class="border-t"
                    >
                        <td class="px-2 py-1">
                            {{ formatDateTime(point.at) }}
                        </td>
                        <td class="px-2 py-1 text-right tabular-nums">
                            {{ point.value }}
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </figure>
</template>

<style scoped>
.viz-root {
    --series-1: #2a78d6;
}
</style>
