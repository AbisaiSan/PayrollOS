<script setup lang="ts">
import Icone from '@/Components/Icone.vue';

/**
 * Faixa de aviso contextual. Usada onde a tela precisa explicar uma consequência
 * antes de o usuário agir — não para decorar.
 */
withDefaults(
    defineProps<{
        tom?: 'neutro' | 'atencao';
        icone?: string;
    }>(),
    { tom: 'neutro', icone: 'info' },
);
</script>

<template>
    <div
        class="flex items-start gap-2.5 rounded-md border px-3.5 py-3 text-[12.75px]"
        :class="
            tom === 'atencao'
                ? 'border-atencao-line bg-atencao-bg text-[#7A4E00]'
                : 'border-neutro-line bg-neutro-bg text-ink-70'
        "
    >
        <Icone :nome="icone" :tamanho="16" class="mt-px shrink-0" />
        <div><slot /></div>
    </div>
</template>
