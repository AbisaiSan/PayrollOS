<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import CabecalhoPagina from '@/Components/CabecalhoPagina.vue';
import EmConstrucao from '@/Components/EmConstrucao.vue';
</script>

<template>
    <Head title="Reembolso" />

    <AuthenticatedLayout>
        <template #header>
            <CabecalhoPagina titulo="Reembolso" descricao="Solicitação de reembolso" />
        </template>

        <EmConstrucao
            modulo="Solicitação de reembolso"
            fase="Fase 4 do plano"
            entrega="O upload do comprovante já é tratado por AnexoService."
        />
    </AuthenticatedLayout>
</template>
