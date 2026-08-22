<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import Button from 'primevue/button';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import CabecalhoPagina from '@/Components/CabecalhoPagina.vue';
import Icone from '@/Components/Icone.vue';
</script>

<template>
    <Head title="Página não encontrada" />

    <AuthenticatedLayout>
        <template #header>
            <CabecalhoPagina titulo="Página não encontrada" />
        </template>

        <div class="flex flex-col items-center px-4 pb-10 pt-16 text-center">
            <span
                class="mb-4 flex h-14 w-14 items-center justify-center rounded-full bg-ink-8 text-ink-55"
            >
                <Icone nome="search" :tamanho="24" />
            </span>

            <h2 class="text-[17px] font-semibold">Página não encontrada</h2>

            <!--
                As duas causas prováveis, porque levam a caminhos diferentes: link
                velho se resolve navegando, registro removido não se resolve
                tentando de novo.
            -->
            <p class="mt-2 max-w-[420px] text-[13px] leading-[1.55] text-ink-55">
                O endereço acessado não existe ou o registro foi removido.
            </p>

            <Link :href="route('dashboard')" class="mt-5">
                <Button label="Voltar ao Dashboard" size="small">
                    <template #icon><Icone nome="home" :tamanho="16" /></template>
                </Button>
            </Link>
        </div>
    </AuthenticatedLayout>
</template>
