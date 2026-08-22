<script setup lang="ts">
import Button from 'primevue/button';
import Icone from '@/Components/Icone.vue';

/**
 * Os estados que toda listagem precisa (tarefa 40), definidos uma vez.
 *
 * `vazio-filtro` e `vazio` parecem a mesma tela e não são: um diz que a busca
 * não achou nada e a saída é mexer no filtro; o outro diz que ainda não existe
 * nada e a saída é cadastrar. Trocar um pelo outro manda a pessoa para o lugar
 * errado — daí serem variantes explícitas em vez de um texto genérico.
 */
withDefaults(
    defineProps<{
        variante: 'vazio-filtro' | 'vazio' | 'erro';
        titulo: string;
        descricao?: string;
        icone?: string;
        acao?: string;
    }>(),
    { icone: 'info' },
);

const emit = defineEmits<{ acao: [] }>();

const ICONE_PADRAO: Record<string, string> = {
    'vazio-filtro': 'filter',
    vazio: 'info',
    erro: 'alertOctagon',
};

const ICONE_ACAO: Record<string, string> = {
    'vazio-filtro': 'x',
    vazio: 'plus',
    erro: 'refresh',
};
</script>

<template>
    <div class="flex flex-col items-center px-4 py-12 text-center">
        <span
            class="mb-3 flex h-12 w-12 items-center justify-center rounded-full"
            :class="variante === 'erro' ? 'bg-perigo-bg text-perigo' : 'bg-ink-8 text-ink-55'"
        >
            <Icone :nome="icone !== 'info' ? icone : ICONE_PADRAO[variante]" :tamanho="22" />
        </span>

        <p class="text-[14px] font-semibold">{{ titulo }}</p>

        <p v-if="descricao" class="mt-1.5 max-w-[400px] text-[12.75px] leading-[1.5] text-ink-55">
            {{ descricao }}
        </p>

        <Button
            v-if="acao"
            :label="acao"
            size="small"
            :severity="variante === 'vazio-filtro' ? 'secondary' : undefined"
            :outlined="variante === 'vazio-filtro'"
            class="mt-4"
            @click="emit('acao')"
        >
            <template #icon><Icone :nome="ICONE_ACAO[variante]" :tamanho="14" /></template>
        </Button>
    </div>
</template>
