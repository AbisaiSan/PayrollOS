<script setup lang="ts">
import { computed, ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import Button from 'primevue/button';
import { useConfirm } from 'primevue/useconfirm';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import CabecalhoPagina from '@/Components/CabecalhoPagina.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import HistoricoStatusTimeline from '@/Components/HistoricoStatusTimeline.vue';
import ModalRejeitarReembolso from '@/Components/ModalRejeitarReembolso.vue';
import Anexos from '@/Components/Anexos.vue';
import Icone from '@/Components/Icone.vue';
import { useFormato } from '@/Composables/useFormato';
import { usePermissoes } from '@/Composables/usePermissoes';
import type { Anexo, ContaBancaria, HistoricoStatus, Opcao } from '@/types';

interface ReembolsoDetalhe {
    id: number;
    descricao: string;
    categoria: string;
    valor: string;
    data_solicitacao: string;
    data_pagamento: string | null;
    status: string;
    observacoes: string | null;
    colaborador: { id: number; nome: string; departamento: string; cpf: string };
    conta_bancaria?: ContaBancaria | null;
    anexos?: Anexo[];
    historico_status?: HistoricoStatus[];
}

const props = defineProps<{
    reembolso: ReembolsoDetalhe;
    /** Vem pronto do backend: é a fonte de verdade das transições, não a tabela do briefing. */
    transicoesPermitidas: Opcao[];
}>();

const { formatarMoeda, formatarData, formatarDocumento, resumoConta } = useFormato();
const { pode } = usePermissoes();
const confirm = useConfirm();

const CATEGORIA: Record<string, string> = {
    viagem: 'Viagem',
    alimentacao: 'Alimentação',
    material: 'Material',
    transporte: 'Transporte',
    outro: 'Outro',
};

const podeIrPara = (status: string) => props.transicoesPermitidas.some((t) => t.value === status);

const dados = computed(() => [
    {
        rotulo: 'Colaborador',
        valor: props.reembolso.colaborador.nome,
        complemento: formatarDocumento(props.reembolso.colaborador.cpf),
    },
    { rotulo: 'Departamento', valor: props.reembolso.colaborador.departamento },
    {
        rotulo: 'Categoria',
        valor: CATEGORIA[props.reembolso.categoria] ?? props.reembolso.categoria,
    },
    {
        rotulo: 'Data da solicitação',
        valor: formatarData(props.reembolso.data_solicitacao),
        mono: true,
    },
    { rotulo: 'Valor', valor: formatarMoeda(props.reembolso.valor), mono: true, destaque: true },
    { rotulo: 'Conta de destino', valor: resumoConta(props.reembolso.conta_bancaria) },
    ...(props.reembolso.data_pagamento
        ? [
              {
                  rotulo: 'Data de pagamento',
                  valor: formatarData(props.reembolso.data_pagamento),
                  mono: true,
              },
          ]
        : []),
]);

/**
 * O comprovante é o motivo de a tela existir: quem aprova precisa vê-lo antes de
 * decidir. O componente de anexos recebe `destacarPrimeiro`, e como a relação vem
 * em `latest()` é o mais recente que sobe: se o colaborador reenviou a nota
 * corrigida, é ela que vale para a decisão.
 */
const anexos = computed(() => props.reembolso.anexos ?? []);

const mudarStatus = (status: string) => {
    router.post(
        route('reembolsos.status', props.reembolso.id),
        { status },
        { preserveScroll: true },
    );
};

const pedirConfirmacao = (opcoes: {
    header: string;
    message: string;
    acceptLabel: string;
    status: string;
    perigo?: boolean;
}) => {
    confirm.require({
        header: opcoes.header,
        message: opcoes.message,
        acceptLabel: opcoes.acceptLabel,
        rejectLabel: 'Voltar',
        // Sem severity o Button fica com o laranja padrão da marca; 'primary' não
        // existe no PrimeVue e cairia num estilo vazio.
        acceptProps: opcoes.perigo
            ? { severity: 'danger', size: 'small' }
            : { size: 'small' },
        rejectProps: { severity: 'secondary', text: true, size: 'small' },
        accept: () => mudarStatus(opcoes.status),
    });
};

const aprovar = () =>
    pedirConfirmacao({
        header: 'Aprovar reembolso',
        message:
            'A solicitação passa para Aprovado e fica liberada para pagamento. A aprovação é registrada na linha do tempo com o seu nome.',
        acceptLabel: 'Aprovar',
        status: 'aprovado',
    });

const registrarPago = () =>
    pedirConfirmacao({
        header: 'Registrar como pago',
        message:
            'O reembolso passa para Pago com a data de hoje. Use esta ação depois de a transferência ter saído de fato.',
        acceptLabel: 'Registrar como pago',
        status: 'pago',
    });

const reabrir = () =>
    pedirConfirmacao({
        header: 'Reabrir solicitação',
        message:
            'A solicitação volta para Pendente e precisa passar de novo pela aprovação. O histórico é preservado.',
        acceptLabel: 'Reabrir',
        status: 'pendente',
    });

/**
 * Pago volta para Aprovado quando a transferência não saiu ou foi devolvida — o
 * serviço limpa a data de pagamento nesse caminho. Chamar isso de "Aprovar", como
 * o protótipo faz, esconderia que a ação desfaz um pagamento.
 */
const desfazerPagamento = () =>
    pedirConfirmacao({
        header: 'Desfazer pagamento',
        message:
            'O reembolso volta para Aprovado e a data de pagamento é apagada. Use quando a transferência não saiu ou foi devolvida.',
        acceptLabel: 'Desfazer pagamento',
        status: 'aprovado',
        perigo: true,
    });

const estaPago = computed(() => props.reembolso.status === 'pago');

const modalRejeitar = ref(false);
</script>

<template>
    <Head :title="reembolso.descricao" />

    <AuthenticatedLayout>
        <template #header>
            <CabecalhoPagina titulo="Reembolso" :descricao="`Solicitação #${reembolso.id}`" />
        </template>

        <Link
            :href="route('reembolsos.index')"
            class="mb-3 inline-flex items-center gap-1.5 rounded-lg px-2.5 py-1.5 text-[12.25px] font-semibold text-ink-70 hover:bg-ink-8"
        >
            <Icone nome="chevronLeft" :tamanho="15" />
            Voltar para reembolsos
        </Link>

        <div class="grid items-start gap-4 lg:grid-cols-[2fr_1fr]">
            <div>
                <!-- Dados da solicitação -->
                <div class="mb-4 rounded-lg border border-ink-8 bg-white shadow-card">
                    <div
                        class="flex items-start justify-between gap-3 border-b border-ink-8 px-5 py-4"
                    >
                        <div class="min-w-0">
                            <h2 class="truncate text-[16px] font-semibold">
                                {{ reembolso.descricao }}
                            </h2>
                            <span class="mono text-[12px] text-ink-55">#{{ reembolso.id }}</span>
                        </div>
                        <StatusBadge :status="reembolso.status" />
                    </div>

                    <div class="px-5 py-5">
                        <dl class="grid gap-x-5 gap-y-3.5 sm:grid-cols-2">
                            <div v-for="campo in dados" :key="campo.rotulo">
                                <dt
                                    class="mb-[3px] text-[11.5px] uppercase tracking-[0.04em] text-ink-55"
                                >
                                    {{ campo.rotulo }}
                                </dt>
                                <dd
                                    class="m-0 font-medium"
                                    :class="[
                                        campo.mono ? 'mono' : '',
                                        campo.destaque ? 'text-[15.5px]' : 'text-[13.75px]',
                                    ]"
                                >
                                    {{ campo.valor }}
                                    <span v-if="campo.complemento" class="font-normal text-ink-55">
                                        ({{ campo.complemento }})
                                    </span>
                                </dd>
                            </div>
                        </dl>

                        <div v-if="reembolso.observacoes" class="mt-5">
                            <dt
                                class="mb-[3px] text-[11.5px] uppercase tracking-[0.04em] text-ink-55"
                            >
                                Observações
                            </dt>
                            <p class="text-[13.25px] text-ink-70">{{ reembolso.observacoes }}</p>
                        </div>
                    </div>

                    <!-- Ações -->
                    <div class="flex flex-wrap items-center gap-2.5 border-t border-ink-8 px-5 py-4">
                        <Button
                            v-if="pode('reembolsos.aprovar') && podeIrPara('aprovado') && !estaPago"
                            label="Aprovar"
                            size="small"
                            @click="aprovar"
                        >
                            <template #icon><Icone nome="checkCircle" :tamanho="16" /></template>
                        </Button>

                        <Button
                            v-if="pode('reembolsos.gerenciar') && podeIrPara('pago')"
                            label="Registrar como pago"
                            size="small"
                            @click="registrarPago"
                        >
                            <template #icon><Icone nome="checkCircle" :tamanho="16" /></template>
                        </Button>

                        <Button
                            v-if="pode('reembolsos.gerenciar') && podeIrPara('rejeitado')"
                            label="Rejeitar"
                            severity="secondary"
                            outlined
                            size="small"
                            @click="modalRejeitar = true"
                        >
                            <template #icon><Icone nome="x" :tamanho="16" /></template>
                        </Button>

                        <Button
                            v-if="pode('reembolsos.gerenciar') && podeIrPara('pendente')"
                            label="Reabrir"
                            severity="secondary"
                            text
                            size="small"
                            @click="reabrir"
                        >
                            <template #icon><Icone nome="refresh" :tamanho="16" /></template>
                        </Button>

                        <Link
                            v-if="pode('reembolsos.gerenciar') && !estaPago"
                            :href="route('reembolsos.edit', reembolso.id)"
                        >
                            <Button label="Editar" severity="secondary" text size="small">
                                <template #icon><Icone nome="edit" :tamanho="16" /></template>
                            </Button>
                        </Link>

                        <Button
                            v-if="pode('reembolsos.gerenciar') && estaPago && podeIrPara('aprovado')"
                            label="Desfazer pagamento"
                            severity="danger"
                            text
                            size="small"
                            class="ml-auto"
                            @click="desfazerPagamento"
                        >
                            <template #icon><Icone nome="refresh" :tamanho="16" /></template>
                        </Button>
                    </div>
                </div>

                <Anexos
                    tipo-registro="reembolso"
                    :registro-id="reembolso.id"
                    :anexos="anexos"
                    :pode-gerenciar="pode('reembolsos.gerenciar') && !estaPago"
                    titulo="Comprovante"
                    vazio="Nenhum comprovante anexado a esta solicitação."
                    destacar-primeiro
                />
            </div>

            <!-- Linha do tempo -->
            <div class="rounded-lg border border-ink-8 bg-white p-5 shadow-card">
                <h2 class="mb-3.5 text-[12px] font-semibold uppercase tracking-[0.06em] text-ink-55">
                    Linha do tempo
                </h2>
                <HistoricoStatusTimeline :eventos="reembolso.historico_status ?? []" />
            </div>
        </div>

        <ModalRejeitarReembolso v-model:visivel="modalRejeitar" :reembolso="reembolso" />
    </AuthenticatedLayout>
</template>
