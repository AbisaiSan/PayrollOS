<script setup lang="ts">
/**
 * Esqueleto de carregamento da grid (tarefa 40).
 *
 * Ocupa a mesma altura das linhas reais de propósito: um spinner faria a página
 * encolher e voltar a crescer, e o conteúdo abaixo pularia no meio da leitura.
 */
withDefaults(
    defineProps<{
        colunas: number;
        linhas?: number;
    }>(),
    { linhas: 5 },
);

/** Larguras irregulares, senão o bloco lê como tabela pronta e não como espera. */
const LARGURAS = ['70%', '55%', '62%', '48%', '58%'];
</script>

<template>
    <div class="px-4 py-3" role="status" aria-live="polite">
        <span class="sr-only">Carregando…</span>

        <div
            v-for="linha in linhas"
            :key="linha"
            class="flex items-center gap-4 border-b border-ink-8 py-3 last:border-b-0"
        >
            <div
                v-for="coluna in colunas"
                :key="coluna"
                class="flex-1"
                :class="coluna === colunas ? 'max-w-[90px]' : ''"
            >
                <div
                    class="skeleton h-[13px]"
                    :style="{ width: LARGURAS[(linha + coluna) % LARGURAS.length] }"
                />
                <div
                    v-if="coluna === 1"
                    class="skeleton mt-1.5 h-[10px]"
                    style="width: 42%"
                />
            </div>
        </div>
    </div>
</template>
