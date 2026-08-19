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
            <CabecalhoPagina titulo="Reembolso" descricao="Detalhes do reembolso" />
        </template>

        <EmConstrucao
            modulo="Detalhe do reembolso"
            fase="Fase 4 do plano"
            entrega="Mudança de status com registro em historico_status."
        />
    </AuthenticatedLayout>
</template>
