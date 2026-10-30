import { usePage } from '@inertiajs/vue3';
import { BatteryCharging, Gauge, MapPin, UserCog } from '@lucide/vue';
import type { ComputedRef } from 'vue';
import { computed } from 'vue';
import SiteController from '@/actions/App/Http/Controllers/SiteController';
import StaffController from '@/actions/App/Http/Controllers/StaffController';
import UpsUnitController from '@/actions/App/Http/Controllers/UpsUnitController';
import { dashboard } from '@/routes';
import type { NavItem } from '@/types';

export type NavGroup = { label: string; items: NavItem[] };

/** Stisla's sidebar menu: monitoring for everyone, administration for admins. */
export function useNavigation(): ComputedRef<NavGroup[]> {
    const page = usePage();

    return computed(() => {
        const groups: NavGroup[] = [
            {
                label: 'Monitoring',
                items: [
                    { title: 'Fleet status', href: dashboard(), icon: Gauge },
                    {
                        title: 'UPS units',
                        href: UpsUnitController.index(),
                        icon: BatteryCharging,
                    },
                ],
            },
        ];

        if (page.props.auth.user.role === 'admin') {
            groups.push({
                label: 'Administration',
                items: [
                    {
                        title: 'Regions & sites',
                        href: SiteController.index(),
                        icon: MapPin,
                    },
                    {
                        title: 'Staff accounts',
                        href: StaffController.index(),
                        icon: UserCog,
                    },
                ],
            });
        }

        return groups;
    });
}
