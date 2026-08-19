<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import CabecalhoPagina from '@/Components/CabecalhoPagina.vue';
import EmConstrucao from '@/Components/EmConstrucao.vue';
</script>

<template>
    <Head title="Auditoria" />

    <AuthenticatedLayout>
        <template #header>
            <CabecalhoPagina titulo="Auditoria" descricao="Histórico de alterações" />
        </template>

        <EmConstrucao
            modulo="Auditoria"
            fase="Fase 5 do plano"
            entrega="O activitylog já registra alterações em colaboradores, fornecedores, contas e pagamentos."
        />
    </AuthenticatedLayout>
</template>
