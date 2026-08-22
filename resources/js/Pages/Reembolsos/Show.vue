<script setup lang="ts">
import { computed } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import Button from 'primevue/button';
import { useConfirm } from 'primevue/useconfirm';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import CabecalhoPagina from '@/Components/CabecalhoPagina.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import HistoricoStatusTimeline from '@/Components/HistoricoStatusTimeline.vue';
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

const { formatarMoeda, formatarData, formatarDataHora, formatarDocumento, resumoConta } =
    useFormato();
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
 * decidir, então ele fica destacado sozinho em vez de virar mais uma linha de
 * lista. A relação vem em `latest()`, e é o anexo mais recente que sobe para o
 * destaque de propósito: se o colaborador reenviou a nota corrigida, é ela que
 * vale para a decisão. Os anteriores continuam acessíveis logo abaixo.
 */
const anexos = computed(() => props.reembolso.anexos ?? []);
const comprovante = computed<Anexo | null>(() => anexos.value[0] ?? null);
const anexosAnteriores = computed(() => anexos.value.slice(1));

const formatarTamanho = (bytes: number) => {
    if (bytes < 1024) return `${bytes} B`;

    const kb = bytes / 1024;

    return kb < 1024 ? `${Math.round(kb)} KB` : `${(kb / 1024).toFixed(1)} MB`;
};

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
                            disabled
                            title="O modal de rejeição, com o motivo obrigatório, entra na próxima etapa (tarefa 12)"
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

                <!-- Comprovante em destaque; o componente completo de anexos é a tarefa 30 -->
                <div class="rounded-lg border border-ink-8 bg-white p-5 shadow-card">
                    <h2
                        class="mb-3.5 text-[12px] font-semibold uppercase tracking-[0.06em] text-ink-55"
                    >
                        Comprovante
                    </h2>

                    <div
                        v-if="comprovante"
                        class="flex flex-wrap items-center justify-between gap-4 rounded-md border border-azul-100 bg-azul-50 px-4 py-4"
                    >
                        <div class="flex min-w-0 items-center gap-3.5">
                            <span
                                class="flex h-11 w-11 shrink-0 items-center justify-center rounded-md border border-azul-100 bg-white text-corebanx-azul"
                            >
                                <Icone nome="paperclip" :tamanho="20" />
                            </span>
                            <div class="min-w-0">
                                <p class="truncate text-[13.75px] font-semibold">
                                    {{ comprovante.nome_arquivo }}
                                </p>
                                <p class="mt-0.5 text-[12px] text-ink-55">
                                    <span class="mono">{{
                                        formatarTamanho(comprovante.tamanho)
                                    }}</span>
                                    · Enviado por
                                    {{ comprovante.enviado_por?.name ?? 'sistema' }} ·
                                    <span class="mono">{{
                                        formatarDataHora(comprovante.created_at)
                                    }}</span>
                                </p>
                            </div>
                        </div>

                        <a :href="route('anexos.download', comprovante.id)">
                            <Button label="Baixar comprovante" severity="secondary" size="small">
                                <template #icon><Icone nome="download" :tamanho="16" /></template>
                            </Button>
                        </a>
                    </div>

                    <div
                        v-else
                        class="rounded-md border border-dashed border-ink-16 px-4 py-6 text-center"
                    >
                        <Icone nome="paperclip" :tamanho="20" class="mx-auto mb-2 text-ink-35" />
                        <p class="text-[12.75px] text-ink-55">
                            Nenhum comprovante anexado a esta solicitação.
                        </p>
                    </div>

                    <template v-if="anexosAnteriores.length">
                        <h3
                            class="mb-2.5 mt-5 text-[12px] font-semibold uppercase tracking-[0.06em] text-ink-55"
                        >
                            Enviados antes
                        </h3>

                        <div
                            v-for="anexo in anexosAnteriores"
                            :key="anexo.id"
                            class="mb-2.5 flex items-center justify-between gap-2 rounded-md border border-ink-8 px-3.5 py-3"
                        >
                            <div class="flex min-w-0 items-center gap-2.5">
                                <Icone nome="paperclip" :tamanho="18" class="shrink-0 text-ink-55" />
                                <div class="min-w-0">
                                    <p class="truncate text-[13px] font-semibold">
                                        {{ anexo.nome_arquivo }}
                                    </p>
                                    <p class="text-[12px] text-ink-55">
                                        Enviado por {{ anexo.enviado_por?.name ?? 'sistema' }} ·
                                        <span class="mono">{{
                                            formatarDataHora(anexo.created_at)
                                        }}</span>
                                    </p>
                                </div>
                            </div>

                            <a
                                :href="route('anexos.download', anexo.id)"
                                class="shrink-0 rounded-lg p-2 text-ink-70 hover:bg-ink-8"
                                :title="`Baixar ${anexo.nome_arquivo}`"
                            >
                                <Icone nome="download" :tamanho="15" />
                            </a>
                        </div>
                    </template>
                </div>
            </div>

            <!-- Linha do tempo -->
            <div class="rounded-lg border border-ink-8 bg-white p-5 shadow-card">
                <h2 class="mb-3.5 text-[12px] font-semibold uppercase tracking-[0.06em] text-ink-55">
                    Linha do tempo
                </h2>
                <HistoricoStatusTimeline :eventos="reembolso.historico_status ?? []" />
            </div>
        </div>
    </AuthenticatedLayout>
</template>
