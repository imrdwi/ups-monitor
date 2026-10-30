<script setup lang="ts">
import { Form, Head, Link, usePage } from '@inertiajs/vue3';
import StaffController from '@/actions/App/Http/Controllers/StaffController';
import FormField from '@/components/admin/FormField.vue';
import NativeSelect from '@/components/admin/NativeSelect.vue';
import PageHeader from '@/components/admin/PageHeader.vue';
import PasswordInput from '@/components/PasswordInput.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';

const props = defineProps<{
    staffMember: {
        id: number;
        name: string;
        email: string;
        role: string;
    } | null;
}>();

const isSelf = props.staffMember?.id === usePage().props.auth.user.id;
const title = props.staffMember
    ? `Edit ${props.staffMember.name}`
    : 'New account';

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Staff accounts', href: StaffController.index() },
            { title: 'Form', href: '#' },
        ],
    },
});
</script>

<template>
    <Head :title="title" />

    <div class="flex flex-1 flex-col gap-6">
        <PageHeader :title="title" />

        <Form
            v-bind="
                staffMember
                    ? StaffController.update.form(staffMember.id)
                    : StaffController.store.form()
            "
            class="max-w-2xl space-y-5 stisla-card p-[25px]"
            v-slot="{ errors, processing }"
        >
            <FormField label="Name" for="name" :error="errors.name">
                <Input
                    id="name"
                    name="name"
                    :default-value="staffMember?.name"
                    required
                />
            </FormField>
            <FormField label="Email" for="email" :error="errors.email">
                <Input
                    id="email"
                    name="email"
                    type="email"
                    :default-value="staffMember?.email"
                    required
                />
            </FormField>
            <FormField
                label="Role"
                for="role"
                :error="errors.role"
                :hint="isSelf ? 'You cannot change your own role.' : undefined"
            >
                <NativeSelect id="role" name="role" :disabled="isSelf">
                    <option
                        value="technician"
                        :selected="staffMember?.role !== 'admin'"
                    >
                        Technician
                    </option>
                    <option
                        value="admin"
                        :selected="staffMember?.role === 'admin'"
                    >
                        Admin
                    </option>
                </NativeSelect>
                <input
                    v-if="isSelf"
                    type="hidden"
                    name="role"
                    :value="staffMember?.role"
                />
            </FormField>
            <FormField
                label="Password"
                for="password"
                :error="errors.password"
                :hint="
                    staffMember
                        ? 'Leave blank to keep the current password.'
                        : undefined
                "
            >
                <PasswordInput
                    id="password"
                    name="password"
                    autocomplete="new-password"
                    :required="!staffMember"
                />
            </FormField>
            <div class="flex gap-2">
                <Button :disabled="processing">Save</Button>
                <Button variant="ghost" as-child>
                    <Link :href="StaffController.index()">Cancel</Link>
                </Button>
            </div>
        </Form>
    </div>
</template>
