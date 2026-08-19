<script setup lang="ts">
/**
 * Marcador das telas cujo backend ja existe mas cuja interface entra numa fase
 * seguinte do plano. Mantem a navegacao inteira sem simular funcionalidade.
 */
defineProps<{
    modulo: string;
    fase: string;
    entrega: string;
}>();
</script>

<template>
    <div
        class="mx-auto max-w-lg rounded-xl border border-dashed border-black/15 bg-white px-8 py-14 text-center"
    >
        <i class="pi pi-wrench text-2xl text-corebanx-preto/25" />

        <h2 class="mt-4 text-base font-semibold text-corebanx-preto">
            {{ modulo }}
        </h2>

        <p class="mt-2 text-sm leading-relaxed text-corebanx-preto/55">
            {{ entrega }}
        </p>

        <p
            class="mt-5 inline-block rounded-full bg-corebanx-cinza px-3 py-1 text-xs font-medium text-corebanx-preto/60"
        >
            {{ fase }}
        </p>
    </div>
</template>
