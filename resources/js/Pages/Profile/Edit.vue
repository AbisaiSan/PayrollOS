<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import CabecalhoPagina from '@/Components/CabecalhoPagina.vue';
import DeleteUserForm from './Partials/DeleteUserForm.vue';
import UpdatePasswordForm from './Partials/UpdatePasswordForm.vue';
import UpdateProfileInformationForm from './Partials/UpdateProfileInformationForm.vue';

defineProps<{
    mustVerifyEmail?: boolean;
    status?: string;
}>();
</script>

<template>
    <Head title="Meu perfil" />

    <AuthenticatedLayout>
        <template #header>
            <CabecalhoPagina titulo="Meu perfil" descricao="Dados de acesso e conta" />
        </template>

        <div class="max-w-[720px] space-y-4">
            <div class="rounded-lg border border-ink-8 bg-white p-5 shadow-card">
                <UpdateProfileInformationForm
                    :must-verify-email="mustVerifyEmail"
                    :status="status"
                />
            </div>

            <div class="rounded-lg border border-ink-8 bg-white p-5 shadow-card">
                <UpdatePasswordForm />
            </div>

            <!-- Cartão com borda de perigo: a ação daqui não se desfaz. -->
            <div class="rounded-lg border border-perigo-line bg-white p-5 shadow-card">
                <DeleteUserForm />
            </div>
        </div>
    </AuthenticatedLayout>
</template>
