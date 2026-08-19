<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import CabecalhoPagina from '@/Components/CabecalhoPagina.vue';
import EmConstrucao from '@/Components/EmConstrucao.vue';
</script>

<template>
    <Head title="Categoria" />

    <AuthenticatedLayout>
        <template #header>
            <CabecalhoPagina titulo="Categoria" descricao="Cadastro de categoria" />
        </template>

        <EmConstrucao
            modulo="Cadastro de categoria"
            fase="Fase 3 do plano"
            entrega="Nome livre, com o tipo fixo por trás usado nos relatórios."
        />
    </AuthenticatedLayout>
</template>
