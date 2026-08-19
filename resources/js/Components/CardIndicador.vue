<script setup lang="ts">
withDefaults(
    defineProps<{
        rotulo: string;
        valor: string;
        detalhe?: string;
        icone?: string;
        /** Destaque visual quando o numero exige acao (ex: atrasados). */
        alerta?: boolean;
    }>(),
    { alerta: false },
);
</script>

<template>
    <div
        class="rounded-xl border bg-white p-5"
        :class="alerta ? 'border-red-200' : 'border-black/5'"
    >
        <div class="flex items-start justify-between gap-3">
            <p class="text-sm font-medium text-corebanx-preto/50">{{ rotulo }}</p>
            <i
                v-if="icone"
                :class="[icone, alerta ? 'text-red-500' : 'text-corebanx-preto/25']"
            />
        </div>

        <p
            class="mt-2 text-2xl font-semibold tabular-nums"
            :class="alerta ? 'text-red-600' : 'text-corebanx-preto'"
        >
            {{ valor }}
        </p>

        <p v-if="detalhe" class="mt-1 text-xs text-corebanx-preto/45">
            {{ detalhe }}
        </p>
    </div>
</template>
