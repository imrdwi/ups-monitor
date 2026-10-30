<script setup lang="ts">
defineProps<{
    title: string;
    description?: string;
}>();
</script>

<template>
    <!-- Stisla's section header: a white bar overlapping the coloured band. -->
    <div
        class="flex flex-wrap items-center justify-between gap-4 rounded-[3px] border-t border-[#f9f9f9] bg-white px-[35px] py-5 shadow-[0_4px_8px_rgba(0,0,0,0.03)]"
    >
        <div class="space-y-1">
            <h1 class="text-2xl font-bold">{{ title }}</h1>
            <p v-if="description" class="text-sm text-muted-foreground">
                {{ description }}
            </p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <slot />
        </div>
    </div>
</template>
