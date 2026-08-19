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
            <CabecalhoPagina titulo="Contrato" descricao="Detalhes do contrato" />
        </template>

        <EmConstrucao
            modulo="Detalhe do contrato"
            fase="Fase 2 do plano"
            entrega="Mostra os pagamentos já gerados pelo contrato e o próximo vencimento previsto."
        />
    </AuthenticatedLayout>
</template>
