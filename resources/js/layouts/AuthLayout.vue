<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { BatteryCharging } from '@lucide/vue';

const { title = '', description = '' } = defineProps<{
    title?: string;
    description?: string;
}>();

const page = usePage();
const year = new Date().getFullYear();
</script>

<template>
    <!-- Stisla's sign-in page: brand, then a card with a coloured top edge. -->
    <div class="min-h-svh bg-[#f4f6f9] px-4 py-16">
        <div class="mx-auto w-full max-w-[420px]">
            <div class="mb-8 flex flex-col items-center gap-3">
                <span
                    class="flex size-[100px] items-center justify-center rounded-full bg-white text-band shadow-[0_4px_8px_rgba(0,0,0,0.03)]"
                    aria-hidden="true"
                >
                    <BatteryCharging class="size-12" />
                </span>
                <p class="font-bold tracking-[0.15em] text-black uppercase">
                    {{ page.props.name }}
                </p>
            </div>
            <div class="stisla-card border-t-2 border-band">
                <div class="border-b border-[#f9f9f9] px-[25px] pt-5 pb-4">
                    <h1 class="text-base font-bold text-band">{{ title }}</h1>
                    <p v-if="description" class="text-sm text-muted-foreground">
                        {{ description }}
                    </p>
                </div>
                <div class="p-[25px]">
                    <slot />
                </div>
            </div>
            <p class="mt-10 text-center text-sm text-[#98a6ad]">
                Copyright &copy; {{ page.props.name }} {{ year }}
            </p>
        </div>
    </div>
</template>
