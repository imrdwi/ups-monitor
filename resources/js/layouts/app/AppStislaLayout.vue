<script setup lang="ts">
import { Link, router, usePage } from '@inertiajs/vue3';
import { Menu } from '@lucide/vue';
import { onUnmounted, ref } from 'vue';
import Breadcrumbs from '@/components/Breadcrumbs.vue';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Toaster } from '@/components/ui/sonner';
import UserMenuContent from '@/components/UserMenuContent.vue';
import { useCurrentUrl } from '@/composables/useCurrentUrl';
import { useInitials } from '@/composables/useInitials';
import { useNavigation } from '@/composables/useNavigation';
import { cn } from '@/lib/utils';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';

withDefaults(defineProps<{ breadcrumbs?: BreadcrumbItem[] }>(), {
    breadcrumbs: () => [],
});

const page = usePage();
const groups = useNavigation();
const { isCurrentOrParentUrl } = useCurrentUrl();
const { getInitials } = useInitials();
const year = new Date().getFullYear();

// Below lg the sidebar slides over the page, as Stisla's did.
const sidebarOpen = ref(false);
onUnmounted(router.on('navigate', () => (sidebarOpen.value = false)));
</script>

<template>
    <div class="relative min-h-svh">
        <!-- The coloured band behind the navbar and the top of the page. -->
        <div class="absolute inset-x-0 top-0 h-[115px] bg-band" />

        <nav
            class="relative z-10 flex h-[70px] items-center justify-between px-4 lg:ml-[250px]"
            aria-label="Top"
        >
            <button
                type="button"
                class="rounded p-2 text-white hover:bg-white/10 lg:invisible"
                aria-label="Open menu"
                @click="sidebarOpen = true"
            >
                <Menu class="size-5" />
            </button>
            <DropdownMenu>
                <DropdownMenuTrigger
                    class="flex items-center gap-2 rounded px-2 py-1 font-semibold text-white hover:bg-white/10"
                >
                    <span
                        class="flex size-[30px] items-center justify-center rounded-full bg-white text-xs font-bold text-band"
                        aria-hidden="true"
                        >{{ getInitials(page.props.auth.user.name) }}</span
                    >
                    <span class="hidden sm:inline"
                        >Hi, {{ page.props.auth.user.name }}</span
                    >
                </DropdownMenuTrigger>
                <DropdownMenuContent align="end" class="w-56">
                    <UserMenuContent :user="page.props.auth.user" />
                </DropdownMenuContent>
            </DropdownMenu>
        </nav>

        <div
            v-if="sidebarOpen"
            class="fixed inset-0 z-30 bg-black/50 lg:hidden"
            @click="sidebarOpen = false"
        />
        <aside
            :class="
                cn(
                    'fixed inset-y-0 left-0 z-40 w-[250px] overflow-y-auto bg-white shadow-[0_4px_8px_rgba(0,0,0,0.03)] transition-transform lg:translate-x-0',
                    sidebarOpen ? 'translate-x-0' : '-translate-x-full',
                )
            "
        >
            <Link
                :href="dashboard()"
                class="block h-[60px] text-center leading-[60px] font-bold tracking-[0.15em] text-black uppercase"
            >
                {{ page.props.name }}
            </Link>
            <template v-for="(group, index) in groups" :key="group.label">
                <hr v-if="index > 0" class="my-2 border-[#f2f2f2]" />
                <p
                    class="px-5 pt-5 pb-1 text-[10px] font-extrabold tracking-[0.1em] text-[#a1a8ae] uppercase"
                >
                    {{ group.label }}
                </p>
                <ul>
                    <li v-for="item in group.items" :key="item.title">
                        <Link
                            :href="item.href"
                            :class="
                                cn(
                                    'relative flex h-[50px] items-center gap-4 px-5 font-medium text-[#868e96] hover:text-black',
                                    isCurrentOrParentUrl(item.href) &&
                                        'font-semibold text-band before:absolute before:inset-y-3 before:left-0 before:w-1 before:bg-band hover:text-band',
                                )
                            "
                        >
                            <component :is="item.icon" class="size-4" />
                            {{ item.title }}
                        </Link>
                    </li>
                </ul>
            </template>
        </aside>

        <main class="relative px-4 pt-2.5 pb-8 lg:ml-[250px] lg:px-[30px]">
            <Breadcrumbs
                v-if="breadcrumbs.length > 1"
                :breadcrumbs="breadcrumbs"
                class="mb-3 [&_a]:text-white/80 [&_a:hover]:text-white [&_li]:text-white/80 [&_span]:text-white"
            />
            <slot />
        </main>

        <footer
            class="border-t border-[#e3eaef] px-[30px] py-5 text-[#98a6ad] lg:ml-[250px]"
        >
            Copyright &copy; {{ year }}
            <span class="mx-1.5" aria-hidden="true">&bull;</span>
            {{ page.props.name }}
        </footer>
        <Toaster />
    </div>
</template>
