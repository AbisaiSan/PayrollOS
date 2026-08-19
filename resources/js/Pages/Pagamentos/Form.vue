<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import CabecalhoPagina from '@/Components/CabecalhoPagina.vue';
import EmConstrucao from '@/Components/EmConstrucao.vue';
</script>

<template>
    <Head title="Lançamento" />

    <AuthenticatedLayout>
        <template #header>
            <CabecalhoPagina titulo="Lançamento" descricao="Lançamento de pagamento" />
        </template>

        <EmConstrucao
            modulo="Lançamento de pagamento"
            fase="Fase 3 do plano"
            entrega="PagamentoService já valida beneficiário, conta de destino e transições de status."
        />
    </AuthenticatedLayout>
</template>
