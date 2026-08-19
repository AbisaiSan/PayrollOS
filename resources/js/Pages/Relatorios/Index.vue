<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import CabecalhoPagina from '@/Components/CabecalhoPagina.vue';
import EmConstrucao from '@/Components/EmConstrucao.vue';
</script>

<template>
    <Head title="Relatórios" />

    <AuthenticatedLayout>
        <template #header>
            <CabecalhoPagina titulo="Relatórios" descricao="Consolidado por período e categoria" />
        </template>

        <EmConstrucao
            modulo="Relatórios"
            fase="Fase 6 do plano"
            entrega="Os agregados por status e categoria já vêm do backend. Falta a visualização e a exportação Excel/PDF."
        />
    </AuthenticatedLayout>
</template>
