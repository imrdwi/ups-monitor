<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import PageHeader from '@/components/admin/PageHeader.vue';
import { useCurrentUrl } from '@/composables/useCurrentUrl';
import { cn } from '@/lib/utils';
import { edit as editProfile } from '@/routes/profile';
import { edit as editSecurity } from '@/routes/security';
import type { NavItem } from '@/types';

const items: NavItem[] = [
    { title: 'Profile', href: editProfile() },
    { title: 'Security', href: editSecurity() },
];

const { isCurrentOrParentUrl } = useCurrentUrl();
</script>

<template>
    <div class="flex flex-col gap-6">
        <PageHeader
            title="Settings"
            description="Manage your profile and account settings"
        />

        <div class="flex flex-col gap-6 lg:flex-row lg:items-start">
            <nav
                class="w-full shrink-0 stisla-card py-2 lg:w-56"
                aria-label="Settings"
            >
                <Link
                    v-for="item in items"
                    :key="item.title"
                    :href="item.href"
                    :class="
                        cn(
                            'block px-5 py-2.5 font-semibold text-[#868e96] hover:text-black',
                            isCurrentOrParentUrl(item.href) && 'text-band',
                        )
                    "
                >
                    {{ item.title }}
                </Link>
            </nav>
            <section class="flex-1 space-y-12 stisla-card p-[25px]">
                <slot />
            </section>
        </div>
    </div>
</template>
