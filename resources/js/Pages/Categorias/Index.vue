<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import CabecalhoPagina from '@/Components/CabecalhoPagina.vue';
import EmConstrucao from '@/Components/EmConstrucao.vue';
</script>

<template>
    <Head title="Categorias" />

    <AuthenticatedLayout>
        <template #header>
            <CabecalhoPagina titulo="Categorias" descricao="Categorias de pagamento" />
        </template>

        <EmConstrucao
            modulo="Categorias de pagamento"
            fase="Fase 3 do plano"
            entrega="As categorias iniciais já vêm do seeder e podem ser ajustadas sem deploy."
        />
    </AuthenticatedLayout>
</template>
