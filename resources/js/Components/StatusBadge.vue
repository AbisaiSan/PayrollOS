<script setup lang="ts">
import { computed } from 'vue';
import Tag from 'primevue/tag';

const props = defineProps<{
    status: string;
    /** Espelha o rotulo do enum PHP; sem ele, o proprio valor e exibido. */
    rotulo?: string;
}>();

/** Mesmas severidades de StatusPagamento::severidade() e StatusReembolso::severidade(). */
const severidades: Record<string, string> = {
    pendente: 'warn',
    agendado: 'info',
    pago: 'success',
    atrasado: 'danger',
    cancelado: 'secondary',
    aprovado: 'info',
    rejeitado: 'danger',
    ativo: 'success',
    ativa: 'success',
    inativo: 'secondary',
    inativa: 'secondary',
    afastado: 'warn',
    desligado: 'secondary',
    suspenso: 'warn',
    encerrado: 'secondary',
};

const rotulos: Record<string, string> = {
    pendente: 'Pendente',
    agendado: 'Agendado',
    pago: 'Pago',
    atrasado: 'Atrasado',
    cancelado: 'Cancelado',
    aprovado: 'Aprovado',
    rejeitado: 'Rejeitado',
    ativo: 'Ativo',
    ativa: 'Ativa',
    inativo: 'Inativo',
    inativa: 'Inativa',
    afastado: 'Afastado',
    desligado: 'Desligado',
    suspenso: 'Suspenso',
    encerrado: 'Encerrado',
};

const severidade = computed(() => severidades[props.status] ?? 'secondary');
const texto = computed(() => props.rotulo ?? rotulos[props.status] ?? props.status);
</script>

<template>
    <Tag :severity="severidade" :value="texto" rounded />
</template>
