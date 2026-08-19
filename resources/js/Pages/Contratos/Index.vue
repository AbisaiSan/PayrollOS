<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import CabecalhoPagina from '@/Components/CabecalhoPagina.vue';
import EmConstrucao from '@/Components/EmConstrucao.vue';
</script>

<template>
    <Head title="Contratos" />

    <AuthenticatedLayout>
        <template #header>
            <CabecalhoPagina titulo="Contratos" descricao="Serviços recorrentes contratados" />
        </template>

        <EmConstrucao
            modulo="Listagem de contratos"
            fase="Fase 2 do plano"
            entrega="A rotina que gera lançamentos a partir dos contratos recorrentes já roda via payrollos:gerar-lancamentos."
        />
    </AuthenticatedLayout>
</template>
