<script setup lang="ts">
defineProps<{
    empty?: boolean;
    emptyText?: string;
}>();
</script>

<template>
    <div class="overflow-x-auto stisla-card">
        <table
            class="w-full text-left text-sm [&_tbody_tr]:border-t [&_tbody_tr]:border-[#f2f2f2] [&_tbody_tr:hover]:bg-[#fafbff] [&_td]:h-[60px] [&_td]:px-6 [&_td]:py-2 [&_th]:px-6 [&_th]:py-3.5 [&_th]:font-semibold [&_th]:text-[#666]"
        >
            <thead class="bg-[rgba(0,0,0,0.04)]">
                <slot name="head" />
            </thead>
            <tbody>
                <slot />
                <tr v-if="empty">
                    <td colspan="100" class="text-center text-muted-foreground">
                        {{ emptyText ?? 'Nothing here yet.' }}
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</template>
