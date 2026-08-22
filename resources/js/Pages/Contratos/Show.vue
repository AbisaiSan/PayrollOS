<script setup lang="ts">
import { computed } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Button from 'primevue/button';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import CabecalhoPagina from '@/Components/CabecalhoPagina.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import Aviso from '@/Components/Aviso.vue';
import Anexos from '@/Components/Anexos.vue';
import Icone from '@/Components/Icone.vue';
import { useFormato } from '@/Composables/useFormato';
import { usePermissoes } from '@/Composables/usePermissoes';
import type { Anexo, ContaBancaria, Fornecedor } from '@/types';

interface ContratoDetalhe {
    id: number;
    descricao: string;
    tipo: string;
    valor: string;
    periodicidade: string | null;
    dia_vencimento: number | null;
    data_inicio: string;
    data_fim: string | null;
    proximo_vencimento: string | null;
    status: string;
    observacoes: string | null;
    fornecedor: Fornecedor;
    categoria?: { id: number; nome: string } | null;
    conta_bancaria?: ContaBancaria | null;
    anexos?: Anexo[];
}

interface LinhaPagamento {
    id: number;
    descricao: string;
    valor: string;
    data_vencimento: string;
    status: string;
}

const props = defineProps<{
    contrato: ContratoDetalhe;
    pagamentosGerados: LinhaPagamento[];
}>();

const { formatarMoeda, formatarData, vencimentoRelativo, resumoConta } = useFormato();
const { pode } = usePermissoes();

const PERIODICIDADE: Record<string, string> = {
    mensal: 'Mensal',
    quinzenal: 'Quinzenal',
    anual: 'Anual',
};

const ehRecorrente = computed(() => props.contrato.tipo === 'recorrente');
const estaAtivo = computed(() => props.contrato.status === 'ativo');

/**
 * A rotina das 6h só gera lançamento para contrato ativo. Fora disso a data
 * guardada é resíduo, e exibi-la como compromisso prometeria um lançamento que
 * não vai nascer.
 */
const geraLancamentos = computed(() => ehRecorrente.value && estaAtivo.value);

const campos = computed(() => [
    { rotulo: 'Fornecedor', valor: props.contrato.fornecedor.razao_social },
    { rotulo: 'Categoria', valor: props.contrato.categoria?.nome ?? '—' },
    { rotulo: 'Tipo', valor: ehRecorrente.value ? 'Recorrente' : 'Pontual' },
    { rotulo: 'Valor', valor: formatarMoeda(props.contrato.valor), mono: true, destaque: true },
    ...(ehRecorrente.value
        ? [
              {
                  rotulo: 'Periodicidade',
                  valor: props.contrato.periodicidade
                      ? (PERIODICIDADE[props.contrato.periodicidade] ??
                        props.contrato.periodicidade)
                      : '—',
              },
              {
                  rotulo: 'Dia de vencimento',
                  valor: props.contrato.dia_vencimento?.toString() ?? '—',
                  mono: true,
              },
          ]
        : []),
    { rotulo: 'Início', valor: formatarData(props.contrato.data_inicio), mono: true },
    { rotulo: 'Término', valor: formatarData(props.contrato.data_fim), mono: true },
    {
        rotulo: 'Próximo vencimento',
        valor: geraLancamentos.value ? formatarData(props.contrato.proximo_vencimento) : '—',
        complemento: geraLancamentos.value
            ? (vencimentoRelativo(props.contrato.proximo_vencimento) ?? undefined)
            : undefined,
        mono: true,
    },
    { rotulo: 'Conta de destino', valor: resumoConta(props.contrato.conta_bancaria) },
]);

const anexos = computed(() => props.contrato.anexos ?? []);

const podeVerPagamentos = computed(() => pode('pagamentos.ver'));

const abrirPagamento = (evento: { data: LinhaPagamento }) => {
    if (!podeVerPagamentos.value) return;

    router.get(route('pagamentos.show', evento.data.id));
};

const classeLinha = () => (podeVerPagamentos.value ? 'cursor-pointer' : '');
</script>

<template>
    <Head :title="contrato.descricao" />

    <AuthenticatedLayout>
        <template #header>
            <CabecalhoPagina titulo="Contrato" :descricao="contrato.descricao" />
        </template>

        <Link
            :href="route('contratos.index')"
            class="mb-3 inline-flex items-center gap-1.5 rounded-lg px-2.5 py-1.5 text-[12.25px] font-semibold text-ink-70 hover:bg-ink-8"
        >
            <Icone nome="chevronLeft" :tamanho="15" />
            Voltar para contratos
        </Link>

        <!--
            O que a rotina das 6h vai ou não fazer com este contrato é a pergunta
            que traz alguém a esta tela. Responder no topo evita procurar a resposta
            cruzando status, tipo e data.
        -->
        <Aviso v-if="ehRecorrente && !estaAtivo" tom="atencao" icone="alertTriangle" class="mb-4">
            Contrato {{ contrato.status === 'suspenso' ? 'suspenso' : 'encerrado' }}. A rotina
            das 06:00 não gera mais lançamentos a partir dele; os já criados seguem na listagem
            de pagamentos.
        </Aviso>

        <Aviso v-else-if="geraLancamentos" class="mb-4">
            Este contrato gera lançamentos sozinho. Às 06:00, alguns dias antes do vencimento,
            um pagamento Pendente é criado com o valor e a categoria daqui.
        </Aviso>

        <div class="grid items-start gap-4 lg:grid-cols-[2fr_1fr]">
            <div>
                <!-- Dados do contrato -->
                <div class="mb-4 rounded-lg border border-ink-8 bg-white shadow-card">
                    <div
                        class="flex items-start justify-between gap-3 border-b border-ink-8 px-5 py-4"
                    >
                        <div class="min-w-0">
                            <h2 class="truncate text-[16px] font-semibold">
                                {{ contrato.descricao }}
                            </h2>
                            <Link
                                :href="route('fornecedores.show', contrato.fornecedor.id)"
                                class="text-[12px] text-azul-600 hover:underline"
                            >
                                {{ contrato.fornecedor.razao_social }}
                            </Link>
                        </div>
                        <StatusBadge :status="contrato.status" />
                    </div>

                    <div class="px-5 py-5">
                        <dl class="grid gap-x-5 gap-y-3.5 sm:grid-cols-2">
                            <div v-for="campo in campos" :key="campo.rotulo">
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

                        <div v-if="contrato.observacoes" class="mt-5">
                            <dt
                                class="mb-[3px] text-[11.5px] uppercase tracking-[0.04em] text-ink-55"
                            >
                                Observações
                            </dt>
                            <p class="text-[13.25px] text-ink-70">{{ contrato.observacoes }}</p>
                        </div>
                    </div>

                    <div
                        v-if="pode('contratos.gerenciar')"
                        class="flex flex-wrap items-center gap-2.5 border-t border-ink-8 px-5 py-4"
                    >
                        <Link :href="route('contratos.edit', contrato.id)">
                            <Button label="Editar contrato" severity="secondary" outlined size="small">
                                <template #icon><Icone nome="edit" :tamanho="16" /></template>
                            </Button>
                        </Link>
                    </div>
                </div>

                <!-- Pagamentos gerados -->
                <div class="overflow-hidden rounded-lg border border-ink-8 bg-white shadow-card">
                    <div class="border-b border-ink-8 px-5 py-4">
                        <h2
                            class="text-[12px] font-semibold uppercase tracking-[0.06em] text-ink-55"
                        >
                            Pagamentos gerados por este contrato
                        </h2>
                    </div>

                    <DataTable
                        :value="pagamentosGerados"
                        data-key="id"
                        size="small"
                        :row-class="classeLinha"
                        @row-click="abrirPagamento"
                    >
                        <template #empty>
                            <p class="py-10 text-center text-[13px] text-ink-55">
                                Nenhum pagamento gerado por este contrato ainda.
                            </p>
                        </template>

                        <Column field="descricao" header="Descrição">
                            <template #body="{ data }">
                                <span class="font-semibold text-ink">{{ data.descricao }}</span>
                            </template>
                        </Column>

                        <Column field="data_vencimento" header="Vencimento">
                            <template #body="{ data }">
                                <span class="mono">{{ formatarData(data.data_vencimento) }}</span>
                            </template>
                        </Column>

                        <Column
                            field="valor"
                            header="Valor"
                            class="text-right"
                            header-class="!text-right"
                        >
                            <template #body="{ data }">
                                <span class="mono font-semibold">{{
                                    formatarMoeda(data.valor)
                                }}</span>
                            </template>
                        </Column>

                        <Column field="status" header="Status">
                            <template #body="{ data }">
                                <StatusBadge :status="data.status" />
                            </template>
                        </Column>
                    </DataTable>
                </div>
            </div>

            <Anexos
                tipo-registro="contrato"
                :registro-id="contrato.id"
                :anexos="anexos"
                :pode-gerenciar="pode('contratos.gerenciar')"
                titulo="Documentos"
                vazio="Nenhum documento anexado ao contrato."
            />
        </div>
    </AuthenticatedLayout>
</template>
