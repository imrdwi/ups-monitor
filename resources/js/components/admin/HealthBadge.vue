<script setup lang="ts">
import { CircleAlert, CircleCheck, OctagonAlert } from '@lucide/vue';
import type { Component } from 'vue';
import { computed } from 'vue';
import { cn } from '@/lib/utils';

const props = defineProps<{
    health: string | null;
}>();

const healths: Record<
    string,
    { label: string; icon: Component; class: string }
> = {
    normal: {
        label: 'Normal',
        icon: CircleCheck,
        class: 'bg-[#47c363] text-[#191d21]',
    },
    minor: {
        label: 'Minor issue',
        icon: CircleAlert,
        class: 'bg-[#ffa426] text-[#191d21]',
    },
    major: {
        label: 'Major issue',
        icon: OctagonAlert,
        class: 'bg-[#d93b32] text-white',
    },
};

const current = computed(() => (props.health ? healths[props.health] : null));
</script>

<template>
    <span
        v-if="current"
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
    <span v-else class="text-xs text-muted-foreground">No check yet</span>
</template>
