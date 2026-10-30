<script setup lang="ts">
import {
    BatteryWarning,
    CircleCheck,
    CircleHelp,
    Plug,
    Unplug,
    Zap,
} from '@lucide/vue';
import type { Component } from 'vue';
import { computed } from 'vue';
import { cn } from '@/lib/utils';

const props = defineProps<{
    condition: string;
}>();

/**
 * Live state of a UPS. Status colour always travels with an icon and a label.
 */
const conditions: Record<
    string,
    { label: string; icon: Component; class: string }
> = {
    online: {
        label: 'Online',
        icon: CircleCheck,
        class: 'bg-[#47c363] text-[#191d21]',
    },
    on_battery: {
        label: 'On battery',
        icon: BatteryWarning,
        class: 'bg-[#ffa426] text-[#191d21]',
    },
    bypass: {
        label: 'Bypass',
        icon: Plug,
        class: 'bg-[#3abaf4] text-[#191d21]',
    },
    fault: {
        label: 'Fault',
        icon: Zap,
        class: 'bg-[#d93b32] text-white',
    },
    not_reporting: {
        label: 'Not reporting',
        icon: Unplug,
        class: 'bg-[#6c757d] text-white',
    },
    unknown: {
        label: 'Unknown',
        icon: CircleHelp,
        class: 'bg-[#e3eaef] text-[#34395e]',
    },
};

const current = computed(
    () => conditions[props.condition] ?? conditions.unknown,
);
</script>

<template>
    <span
        :class="
            cn(
                'inline-flex items-center gap-1 rounded-full px-3 py-1 text-xs font-semibold whitespace-nowrap',
                current.class,
            )
        "
    >
        <component :is="current.icon" class="size-3.5" aria-hidden="true" />
        {{ current.label }}
    </span>
</template>
