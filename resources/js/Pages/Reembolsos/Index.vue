<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import CabecalhoPagina from '@/Components/CabecalhoPagina.vue';
import EmConstrucao from '@/Components/EmConstrucao.vue';
</script>

<template>
    <Head title="Reembolsos" />

    <AuthenticatedLayout>
        <template #header>
            <CabecalhoPagina titulo="Reembolsos" descricao="Reembolsos de colaboradores" />
        </template>

        <EmConstrucao
            modulo="Listagem de reembolsos"
            fase="Fase 4 do plano"
            entrega="Fluxo pendente, aprovado, pago e rejeitado já implementado em ReembolsoService."
        />
    </AuthenticatedLayout>
</template>
