<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import SiteController from '@/actions/App/Http/Controllers/SiteController';
import DeleteButton from '@/components/admin/DeleteButton.vue';
import FormField from '@/components/admin/FormField.vue';
import PageHeader from '@/components/admin/PageHeader.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Regions & sites', href: SiteController.index() },
        ],
    },
});

defineProps<{
    regions: {
        id: number;
        name: string;
        sites: {
            id: number;
            code: string;
            name: string;
            address: string | null;
            ups_units_count: number;
        }[];
    }[];
}>();
</script>

<template>
    <Head title="Regions & sites" />

    <div class="flex flex-1 flex-col gap-6">
        <PageHeader
            title="Regions & sites"
            description="Where each UPS is installed."
        />

        <section
            v-for="region in regions"
            :key="region.id"
            class="space-y-3 stisla-card p-4"
        >
            <div class="flex items-center justify-between">
                <h2 class="font-medium">{{ region.name }}</h2>
                <DeleteButton
                    v-if="!region.sites.length"
                    :action="SiteController.destroyRegion.form(region.id)"
                    label="Remove region"
                    :description="`Remove ${region.name}?`"
                />
            </div>

            <ul class="divide-y">
                <li v-for="site in region.sites" :key="site.id" class="py-2">
                    <details>
                        <summary
                            class="flex cursor-pointer items-center justify-between gap-4 text-sm"
                        >
                            <span>
                                <span
                                    class="font-mono text-xs text-muted-foreground"
                                    >{{ site.code }}</span
                                >
                                <span class="ml-2 font-medium">{{
                                    site.name
                                }}</span>
                            </span>
                            <span class="text-muted-foreground"
                                >{{ site.ups_units_count }} UPS</span
                            >
                        </summary>
                        <Form
                            v-bind="SiteController.update.form(site.id)"
                            class="mt-3 grid gap-3 sm:grid-cols-[8rem_1fr_auto]"
                            v-slot="{ errors, processing }"
                        >
                            <input
                                type="hidden"
                                name="region_id"
                                :value="region.id"
                            />
                            <Input
                                name="code"
                                :default-value="site.code"
                                aria-label="Site code"
                                class="font-mono"
                                required
                            />
                            <Input
                                name="name"
                                :default-value="site.name"
                                aria-label="Site name"
                                required
                            />
                            <div class="flex gap-2">
                                <Button size="sm" :disabled="processing"
                                    >Save</Button
                                >
                                <DeleteButton
                                    v-if="!site.ups_units_count"
                                    :action="
                                        SiteController.destroy.form(site.id)
                                    "
                                    label="Remove"
                                    :description="`Remove ${site.name}?`"
                                />
                            </div>
                            <InputError
                                :message="errors.code || errors.name"
                                class="sm:col-span-3"
                            />
                        </Form>
                    </details>
                </li>
            </ul>

            <Form
                v-bind="SiteController.store.form()"
                class="grid gap-3 border-t pt-3 sm:grid-cols-[8rem_1fr_auto]"
                reset-on-success
                v-slot="{ errors, processing }"
            >
                <input type="hidden" name="region_id" :value="region.id" />
                <Input
                    name="code"
                    placeholder="Code"
                    aria-label="New site code"
                    class="font-mono"
                    required
                />
                <Input
                    name="name"
                    placeholder="New site name"
                    aria-label="New site name"
                    required
                />
                <Button size="sm" variant="outline" :disabled="processing"
                    >Add site</Button
                >
                <InputError
                    :message="errors.code || errors.name"
                    class="sm:col-span-3"
                />
            </Form>
        </section>

        <Form
            v-bind="SiteController.storeRegion.form()"
            class="flex max-w-md items-end gap-2"
            reset-on-success
            v-slot="{ errors, processing }"
        >
            <FormField
                label="New region"
                for="region-name"
                :error="errors.name"
                class="flex-1"
            >
                <Input id="region-name" name="name" required />
            </FormField>
            <Button :disabled="processing" variant="outline">Add region</Button>
        </Form>
    </div>
</template>
