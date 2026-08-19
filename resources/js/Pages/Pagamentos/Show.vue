<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import CabecalhoPagina from '@/Components/CabecalhoPagina.vue';
import EmConstrucao from '@/Components/EmConstrucao.vue';
</script>

<template>
    <Head title="Pagamento" />

    <AuthenticatedLayout>
        <template #header>
            <CabecalhoPagina titulo="Pagamento" descricao="Detalhes do lançamento" />
        </template>

        <EmConstrucao
            modulo="Detalhe do pagamento"
            fase="Fase 3 do plano"
            entrega="Inclui confirmação manual, histórico de status e upload de comprovante."
        />
    </AuthenticatedLayout>
</template>
