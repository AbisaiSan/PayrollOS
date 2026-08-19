<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import CabecalhoPagina from '@/Components/CabecalhoPagina.vue';
import EmConstrucao from '@/Components/EmConstrucao.vue';
</script>

<template>
    <Head title="Fornecedores" />

    <AuthenticatedLayout>
        <template #header>
            <CabecalhoPagina titulo="Fornecedores" descricao="Fornecedores e prestadores de serviço" />
        </template>

        <EmConstrucao
            modulo="Listagem de fornecedores"
            fase="Fase 2 do plano"
            entrega="O backend (model, validação de CPF/CNPJ, contas bancárias reaproveitadas) já está pronto. Falta a interface de listagem e filtros."
        />
    </AuthenticatedLayout>
</template>
