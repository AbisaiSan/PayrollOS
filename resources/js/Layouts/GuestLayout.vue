<script setup lang="ts">
import LogoCorebanx from '@/Components/LogoCorebanx.vue';

/**
 * Casca das telas de autenticação. Um cartão só, centralizado, sobre o fundo da
 * aplicação — nada de sidebar nem topbar, porque ainda não há sessão.
 */
defineProps<{
    titulo: string;
    descricao?: string;
}>();
</script>

<template>
    <div class="flex min-h-screen flex-col items-center justify-center bg-app-bg px-4 py-10">
        <div class="w-full max-w-[400px]">
            <div class="rounded-xl border border-ink-8 bg-white px-7 py-7 shadow-pop">
                <LogoCorebanx tom="escuro" class="mb-6" />

                <h1 class="text-[20px] font-semibold -tracking-[0.01em]">{{ titulo }}</h1>
                <p v-if="descricao" class="mb-6 mt-1.5 text-[12.75px] leading-[1.5] text-ink-55">
                    {{ descricao }}
                </p>
                <div v-else class="mb-6" />

                <slot />
            </div>

            <div class="mt-4 text-center">
                <slot name="rodape" />
            </div>
        </div>
    </div>
</template>
