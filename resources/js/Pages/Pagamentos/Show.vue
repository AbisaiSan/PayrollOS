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

interface PagamentoDetalhe {
    id: number;
    descricao: string;
    payable_type: string;
    beneficiario_nome: string;
    categoria?: { id: number; nome: string } | null;
    contrato?: { id: number; descricao: string } | null;
    conta_bancaria?: ContaBancaria | null;
    competencia: string | null;
    valor: string;
    data_vencimento: string;
    data_pagamento: string | null;
    forma_pagamento: string;
    status: string;
    observacoes: string | null;
    anexos?: Anexo[];
    historico_status?: HistoricoStatus[];
    criado_por?: { id: number; name: string } | null;
}

const props = defineProps<{
    pagamento: PagamentoDetalhe;
    /** Vem pronto do backend: é a fonte de verdade das transições, não a tabela do briefing. */
    transicoesPermitidas: Opcao[];
}>();

const { formatarMoeda, formatarData, formatarCompetencia, formatarDataHora, vencimentoRelativo, resumoConta } =
    useFormato();
const { pode } = usePermissoes();
const confirm = useConfirm();

const FORMA_PAGAMENTO: Record<string, string> = {
    pix: 'Pix',
    ted: 'TED',
    boleto: 'Boleto',
    dinheiro: 'Dinheiro',
    outro: 'Outro',
};

const tipoBeneficiario = computed(() =>
    props.pagamento.payable_type === 'colaborador' ? 'Colaborador' : 'Fornecedor',
);

const podeIrPara = (status: string) =>
    props.transicoesPermitidas.some((t) => t.value === status);

const dados = computed(() => [
    { rotulo: 'Beneficiário', valor: props.pagamento.beneficiario_nome, complemento: tipoBeneficiario.value },
    { rotulo: 'Categoria', valor: props.pagamento.categoria?.nome ?? '—' },
    { rotulo: 'Competência', valor: formatarCompetencia(props.pagamento.competencia), mono: true },
    {
        rotulo: 'Vencimento',
        valor: formatarData(props.pagamento.data_vencimento),
        complemento: vencimentoRelativo(props.pagamento.data_vencimento),
        mono: true,
    },
    { rotulo: 'Valor', valor: formatarMoeda(props.pagamento.valor), mono: true, destaque: true },
    {
        rotulo: 'Forma de pagamento',
        valor: FORMA_PAGAMENTO[props.pagamento.forma_pagamento] ?? props.pagamento.forma_pagamento,
    },
    { rotulo: 'Conta de destino', valor: resumoConta(props.pagamento.conta_bancaria) },
    { rotulo: 'Data de pagamento', valor: formatarData(props.pagamento.data_pagamento), mono: true },
]);

const cancelar = () => {
    confirm.require({
        header: 'Cancelar lançamento',
        message:
            'O lançamento passa para Cancelado e sai dos totais em aberto. O histórico é preservado e a ação fica registrada na linha do tempo.',
        acceptLabel: 'Cancelar lançamento',
        rejectLabel: 'Voltar',
        acceptProps: { severity: 'danger', size: 'small' },
        rejectProps: { severity: 'secondary', text: true, size: 'small' },
        accept: () => router.delete(route('pagamentos.destroy', props.pagamento.id)),
    });
};
</script>

<template>
    <Head :title="pagamento.descricao" />

    <AuthenticatedLayout>
        <template #header>
            <CabecalhoPagina titulo="Pagamento" :descricao="`Lançamento #${pagamento.id}`" />
        </template>

        <Link
            :href="route('pagamentos.index')"
            class="mb-3 inline-flex items-center gap-1.5 rounded-lg px-2.5 py-1.5 text-[12.25px] font-semibold text-ink-70 hover:bg-ink-8"
        >
            <Icone nome="chevronLeft" :tamanho="15" />
            Voltar para pagamentos
        </Link>

        <div class="grid items-start gap-4 lg:grid-cols-[2fr_1fr]">
            <div>
                <!-- Dados do lançamento -->
                <div class="mb-4 rounded-lg border border-ink-8 bg-white shadow-card">
                    <div
                        class="flex items-start justify-between gap-3 border-b border-ink-8 px-5 py-4"
                    >
                        <div class="min-w-0">
                            <h2 class="truncate text-[16px] font-semibold">
                                {{ pagamento.descricao }}
                            </h2>
                            <span v-if="pagamento.contrato" class="text-[12px] text-ink-55">
                                Gerado pelo contrato: {{ pagamento.contrato.descricao }}
                            </span>
                        </div>
                        <StatusBadge :status="pagamento.status" />
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
                                    <span
                                        v-if="campo.complemento"
                                        class="font-normal text-ink-55"
                                    >
                                        ({{ campo.complemento }})
                                    </span>
                                </dd>
                            </div>
                        </dl>

                        <div v-if="pagamento.observacoes" class="mt-5">
                            <dt class="mb-[3px] text-[11.5px] uppercase tracking-[0.04em] text-ink-55">
                                Observações
                            </dt>
                            <p class="text-[13.25px] text-ink-70">{{ pagamento.observacoes }}</p>
                        </div>

                        <p v-if="pagamento.criado_por" class="mt-5 text-[11.75px] text-ink-55">
                            Lançado por {{ pagamento.criado_por.name }}
                        </p>
                    </div>

                    <!-- Ações -->
                    <div
                        class="flex flex-wrap items-center gap-2.5 border-t border-ink-8 px-5 py-4"
                    >
                        <Button
                            v-if="pode('pagamentos.confirmar') && podeIrPara('pago')"
                            label="Confirmar pagamento"
                            size="small"
                            disabled
                            title="O modal de confirmação entra na próxima etapa (tarefa 5)"
                        >
                            <template #icon><Icone nome="checkCircle" :tamanho="16" /></template>
                        </Button>

                        <Button
                            v-if="pode('pagamentos.gerenciar') && transicoesPermitidas.length"
                            label="Mudar status"
                            severity="secondary"
                            outlined
                            size="small"
                            disabled
                            title="O modal de mudança de status entra na próxima etapa (tarefa 6)"
                        >
                            <template #icon><Icone nome="refresh" :tamanho="16" /></template>
                        </Button>

                        <Link
                            v-if="pode('pagamentos.gerenciar') && pagamento.status !== 'cancelado'"
                            :href="route('pagamentos.edit', pagamento.id)"
                        >
                            <Button label="Editar lançamento" severity="secondary" text size="small">
                                <template #icon><Icone nome="edit" :tamanho="16" /></template>
                            </Button>
                        </Link>

                        <Button
                            v-if="pode('pagamentos.gerenciar') && podeIrPara('cancelado')"
                            label="Cancelar"
                            severity="danger"
                            text
                            size="small"
                            class="ml-auto"
                            @click="cancelar"
                        >
                            <template #icon><Icone nome="trash" :tamanho="16" /></template>
                        </Button>
                    </div>
                </div>

                <!-- Anexos: versão simples; o componente completo é a tarefa 30 -->
                <div class="rounded-lg border border-ink-8 bg-white p-5 shadow-card">
                    <h2
                        class="mb-3.5 text-[12px] font-semibold uppercase tracking-[0.06em] text-ink-55"
                    >
                        Comprovantes
                    </h2>

                    <div
                        v-for="anexo in pagamento.anexos ?? []"
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
                                    <span class="mono">{{ formatarDataHora(anexo.created_at) }}</span>
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

                    <p
                        v-if="!(pagamento.anexos ?? []).length"
                        class="py-4 text-center text-[12.75px] text-ink-55"
                    >
                        Nenhum comprovante anexado.
                    </p>
                </div>
            </div>

            <!-- Linha do tempo -->
            <div class="rounded-lg border border-ink-8 bg-white p-5 shadow-card">
                <h2 class="mb-3.5 text-[12px] font-semibold uppercase tracking-[0.06em] text-ink-55">
                    Linha do tempo
                </h2>
                <HistoricoStatusTimeline :eventos="pagamento.historico_status ?? []" />
            </div>
        </div>
    </AuthenticatedLayout>
</template>
