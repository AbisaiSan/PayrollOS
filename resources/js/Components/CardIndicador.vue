<script setup lang="ts">
import Icone from '@/Components/Icone.vue';

withDefaults(
    defineProps<{
        rotulo: string;
        valor: string;
        detalhe?: string;
        icone?: string;
        /** Destaque quando o número exige ação — atrasados, por exemplo. */
        alerta?: boolean;
    }>(),
    { icone: 'wallet', alerta: false },
);
</script>

<template>
    <div
        class="rounded-lg border bg-white px-[18px] py-4 shadow-card"
        :class="alerta ? 'border-perigo-line bg-gradient-to-b from-white to-perigo-bg' : 'border-ink-8'"
    >
        <div class="mb-2.5 flex items-center justify-between gap-3">
            <p class="text-[12.25px] font-medium text-ink-55">{{ rotulo }}</p>
            <span
                class="flex h-8 w-8 shrink-0 items-center justify-center rounded-[9px]"
                :class="alerta ? 'bg-perigo-bg text-perigo' : 'bg-azul-50 text-azul-600'"
            >
                <Icone :nome="alerta ? 'alertTriangle' : icone" :tamanho="17" />
            </span>
        </div>

        <p class="mono mb-[3px] mt-0.5 text-[22px] font-semibold -tracking-[0.01em]">
            {{ valor }}
        </p>

        <p
            v-if="detalhe"
            class="text-[11.75px]"
            :class="alerta ? 'font-semibold text-perigo' : 'text-ink-55'"
        >
            {{ detalhe }}
        </p>
    </div>
</template>
