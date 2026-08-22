<script setup lang="ts">
import { computed } from 'vue';
import Icone from '@/Components/Icone.vue';

type Severidade = 'sucesso' | 'info' | 'atencao' | 'perigo' | 'neutro';

interface Definicao {
    rotulo: string;
    severidade: Severidade;
    icone: string;
}

/**
 * Chip de status.
 *
 * Cada severidade tem ícone próprio, não só cor: quem não distingue vermelho de
 * verde ainda precisa achar o atraso varrendo a lista.
 *
 * Os status de pagamento entram aqui na Tarefa 1 e os de reembolso na Tarefa 9;
 * os demais vocabulários (colaborador, fornecedor, contrato) entram nas
 * tarefas 13, 19 e 22.
 */
const PAGAMENTO: Record<string, Definicao> = {
    pendente: { rotulo: 'Pendente', severidade: 'atencao', icone: 'clockOutline' },
    agendado: { rotulo: 'Agendado', severidade: 'info', icone: 'calendar' },
    pago: { rotulo: 'Pago', severidade: 'sucesso', icone: 'checkCircle' },
    atrasado: { rotulo: 'Atrasado', severidade: 'perigo', icone: 'alertTriangle' },
    cancelado: { rotulo: 'Cancelado', severidade: 'neutro', icone: 'slashCircle' },
};

/**
 * Vocabulário de reembolso. "Pendente" e "Pago" já vêm de PAGAMENTO com o mesmo
 * rótulo, severidade e ícone, então só o que é próprio do fluxo de aprovação é
 * declarado aqui.
 */
const REEMBOLSO: Record<string, Definicao> = {
    aprovado: { rotulo: 'Aprovado', severidade: 'info', icone: 'checkCircle' },
    rejeitado: { rotulo: 'Rejeitado', severidade: 'perigo', icone: 'slashCircle' },
};

const DEFINICOES: Record<string, Definicao> = { ...PAGAMENTO, ...REEMBOLSO };

const props = withDefaults(
    defineProps<{
        status: string;
        /** Sobrescreve o rótulo do mapa — use quando o backend já mandar o texto pronto. */
        rotulo?: string;
        tamanho?: 'sm' | 'md';
    }>(),
    { tamanho: 'md' },
);

/**
 * O banco guarda status como string, não ENUM nativo, justamente para aceitar
 * valores novos sem migração (ex: quando o fluxo de aprovação entrar). Um valor
 * desconhecido cai em neutro e mostra o próprio valor, em vez de quebrar a tela.
 */
const definicao = computed<Definicao>(
    () =>
        DEFINICOES[props.status] ?? {
            rotulo: props.status,
            severidade: 'neutro',
            icone: 'info',
        },
);

const texto = computed(() => props.rotulo ?? definicao.value.rotulo);

const CLASSES: Record<Severidade, string> = {
    sucesso: 'bg-sucesso-bg text-sucesso border-sucesso-line',
    info: 'bg-info-bg text-info border-info-line',
    atencao: 'bg-atencao-bg text-atencao border-atencao-line',
    perigo: 'bg-perigo-bg text-perigo border-perigo-line',
    neutro: 'bg-neutro-bg text-neutro border-neutro-line',
};
</script>

<template>
    <span
        class="inline-flex items-center gap-1.5 whitespace-nowrap rounded-full border font-semibold"
        :class="[
            CLASSES[definicao.severidade],
            tamanho === 'sm' ? 'py-0.5 pl-1.5 pr-2 text-[11.5px]' : 'py-1 pl-[7px] pr-[9px] text-[12.25px]',
        ]"
    >
        <Icone :nome="definicao.icone" :tamanho="tamanho === 'sm' ? 12 : 13" />
        {{ texto }}
    </span>
</template>
