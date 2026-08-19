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
            <CabecalhoPagina titulo="Fornecedor" descricao="Cadastro de fornecedor" />
        </template>

        <EmConstrucao
            modulo="Cadastro de fornecedor"
            fase="Fase 2 do plano"
            entrega="Formulário PF/PJ com validação de CPF/CNPJ. As regras de validação já existem em FornecedorRequest."
        />
    </AuthenticatedLayout>
</template>
