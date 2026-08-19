<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import CabecalhoPagina from '@/Components/CabecalhoPagina.vue';
import EmConstrucao from '@/Components/EmConstrucao.vue';
</script>

<template>
    <Head title="Fornecedor" />

    <AuthenticatedLayout>
        <template #header>
            <CabecalhoPagina titulo="Fornecedor" descricao="Detalhes do fornecedor" />
        </template>

        <EmConstrucao
            modulo="Detalhe do fornecedor"
            fase="Fase 2 do plano"
            entrega="Reaproveita o componente ContasBancarias já construído para colaboradores."
        />
    </AuthenticatedLayout>
</template>
