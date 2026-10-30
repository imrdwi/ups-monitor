<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import StaffController from '@/actions/App/Http/Controllers/StaffController';
import AdminTable from '@/components/admin/AdminTable.vue';
import DeleteButton from '@/components/admin/DeleteButton.vue';
import PageHeader from '@/components/admin/PageHeader.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { humanize } from '@/lib/format';

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Staff accounts', href: StaffController.index() },
        ],
    },
});

defineProps<{
    staff: { id: number; name: string; email: string; role: string }[];
}>();

const me = usePage().props.auth.user;
</script>

<template>
    <Head title="Staff accounts" />

    <div class="flex flex-1 flex-col gap-6">
        <PageHeader
            title="Staff accounts"
            description="Technicians record maintenance checks; admins also manage units, sites and accounts."
        >
            <Button as-child>
                <Link :href="StaffController.create()">New account</Link>
            </Button>
        </PageHeader>

        <AdminTable :empty="staff.length === 0">
            <template #head>
                <tr>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th />
                </tr>
            </template>
            <tr v-for="member in staff" :key="member.id">
                <td class="font-medium">{{ member.name }}</td>
                <td>{{ member.email }}</td>
                <td>
                    <Badge variant="secondary">{{
                        humanize(member.role)
                    }}</Badge>
                </td>
                <td class="text-right whitespace-nowrap">
                    <Button variant="ghost" size="sm" as-child>
                        <Link :href="StaffController.edit(member.id)"
                            >Edit</Link
                        >
                    </Button>
                    <DeleteButton
                        v-if="member.id !== me.id"
                        :action="StaffController.destroy.form(member.id)"
                        :description="`Delete ${member.name}'s account?`"
                    />
                </td>
            </tr>
        </AdminTable>
    </div>
</template>
