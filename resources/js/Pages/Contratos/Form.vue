<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import CabecalhoPagina from '@/Components/CabecalhoPagina.vue';
import EmConstrucao from '@/Components/EmConstrucao.vue';
</script>

<template>
    <Head title="Contrato" />

    <AuthenticatedLayout>
        <template #header>
            <CabecalhoPagina titulo="Contrato" descricao="Cadastro de contrato" />
        </template>

        <EmConstrucao
            modulo="Cadastro de contrato"
            fase="Fase 2 do plano"
            entrega="Periodicidade e dia de vencimento alimentam a geração automática de pagamentos."
        />
    </AuthenticatedLayout>
</template>
