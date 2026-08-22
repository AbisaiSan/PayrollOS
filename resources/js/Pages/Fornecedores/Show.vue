<script setup lang="ts">
import { computed } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Button from 'primevue/button';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import CabecalhoPagina from '@/Components/CabecalhoPagina.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import ContasBancarias from '@/Components/ContasBancarias.vue';
import Aviso from '@/Components/Aviso.vue';
import Icone from '@/Components/Icone.vue';
import { useFormato } from '@/Composables/useFormato';
import { usePermissoes } from '@/Composables/usePermissoes';
import type { Fornecedor, Opcao } from '@/types';

interface LinhaContrato {
    id: number;
    descricao: string;
    tipo: string;
    valor: string;
    status: string;
    categoria?: { id: number; nome: string } | null;
}

interface LinhaPagamento {
    id: number;
    descricao: string;
    valor: string;
    data_vencimento: string;
    status: string;
    categoria?: { nome: string } | null;
}

const props = defineProps<{
    fornecedor: Fornecedor & { contratos?: LinhaContrato[] };
    pagamentos: LinhaPagamento[];
    opcoes: { tipoConta: Opcao[]; tipoChavePix: Opcao[] };
}>();

const { formatarMoeda, formatarData, formatarDocumento } = useFormato();
const { pode } = usePermissoes();

const TIPO_FORNECEDOR: Record<string, string> = {
    produto: 'Fornecedor de produto',
    servico: 'Prestador de serviço',
    ambos: 'Ambos',
};

const campos = computed(() => [
    { rotulo: 'Nome fantasia', valor: props.fornecedor.nome_fantasia ?? '—' },
    { rotulo: 'Documento', valor: formatarDocumento(props.fornecedor.documento), mono: true },
    {
        rotulo: 'Tipo de pessoa',
        valor: props.fornecedor.tipo_pessoa === 'pj' ? 'Pessoa Jurídica' : 'Pessoa Física',
    },
    {
        rotulo: 'Tipo de fornecedor',
        valor:
            TIPO_FORNECEDOR[props.fornecedor.tipo_fornecedor] ?? props.fornecedor.tipo_fornecedor,
    },
    { rotulo: 'E-mail', valor: props.fornecedor.email ?? '—' },
    { rotulo: 'Telefone', valor: props.fornecedor.telefone ?? '—' },
]);

const contratos = computed(() => props.fornecedor.contratos ?? []);

const estaInativo = computed(() => props.fornecedor.status === 'inativo');

const podeVerContratos = computed(() => pode('contratos.ver'));
const podeVerPagamentos = computed(() => pode('pagamentos.ver'));

const abrirContrato = (evento: { data: LinhaContrato }) => {
    if (!podeVerContratos.value) return;

    router.get(route('contratos.show', evento.data.id));
};

const abrirPagamento = (evento: { data: LinhaPagamento }) => {
    if (!podeVerPagamentos.value) return;

    router.get(route('pagamentos.show', evento.data.id));
};

const classeContrato = () => (podeVerContratos.value ? 'cursor-pointer' : '');
const classePagamento = () => (podeVerPagamentos.value ? 'cursor-pointer' : '');
</script>

<template>
    <Head :title="fornecedor.razao_social" />

    <AuthenticatedLayout>
        <template #header>
            <CabecalhoPagina titulo="Fornecedor" :descricao="fornecedor.razao_social" />
        </template>

        <Link
            :href="route('fornecedores.index')"
            class="mb-3 inline-flex items-center gap-1.5 rounded-lg px-2.5 py-1.5 text-[12.25px] font-semibold text-ink-70 hover:bg-ink-8"
        >
            <Icone nome="chevronLeft" :tamanho="15" />
            Voltar para fornecedores
        </Link>

        <!--
            Mesma leitura da faixa de desligamento do colaborador: o bloqueio muda o
            que se pode fazer com o cadastro, então vem antes dos blocos.
        -->
        <Aviso v-if="estaInativo" class="mb-4">
            Fornecedor inativo. O histórico permanece visível, mas ele não aceita novos
            lançamentos nem gera pagamentos por contrato recorrente.
        </Aviso>

        <div class="grid items-start gap-4 lg:grid-cols-[2fr_1fr]">
            <div>
                <!-- Dados cadastrais -->
                <div class="mb-4 rounded-lg border border-ink-8 bg-white shadow-card">
                    <div
                        class="flex items-start justify-between gap-3 border-b border-ink-8 px-5 py-4"
                    >
                        <div class="min-w-0">
                            <h2 class="truncate text-[16px] font-semibold">
                                {{ fornecedor.razao_social }}
                            </h2>
                            <span v-if="fornecedor.nome_fantasia" class="text-[12px] text-ink-55">
                                {{ fornecedor.nome_fantasia }}
                            </span>
                        </div>
                        <StatusBadge :status="fornecedor.status" />
                    </div>

                    <div class="px-5 py-5">
                        <h3
                            class="mb-3.5 text-[12px] font-semibold uppercase tracking-[0.06em] text-ink-55"
                        >
                            Dados cadastrais
                        </h3>

                        <dl class="grid gap-x-5 gap-y-3.5 sm:grid-cols-2">
                            <div v-for="campo in campos" :key="campo.rotulo">
                                <dt
                                    class="mb-[3px] text-[11.5px] uppercase tracking-[0.04em] text-ink-55"
                                >
                                    {{ campo.rotulo }}
                                </dt>
                                <dd
                                    class="m-0 text-[13.75px] font-medium"
                                    :class="campo.mono ? 'mono' : ''"
                                >
                                    {{ campo.valor }}
                                </dd>
                            </div>
                        </dl>

                        <div v-if="fornecedor.endereco" class="mt-5">
                            <dt
                                class="mb-[3px] text-[11.5px] uppercase tracking-[0.04em] text-ink-55"
                            >
                                Endereço
                            </dt>
                            <p class="text-[13.25px] text-ink-70">{{ fornecedor.endereco }}</p>
                        </div>

                        <div v-if="fornecedor.observacoes" class="mt-5">
                            <dt
                                class="mb-[3px] text-[11.5px] uppercase tracking-[0.04em] text-ink-55"
                            >
                                Observações
                            </dt>
                            <p class="text-[13.25px] text-ink-70">{{ fornecedor.observacoes }}</p>
                        </div>
                    </div>

                    <div
                        v-if="pode('fornecedores.gerenciar')"
                        class="flex flex-wrap items-center gap-2.5 border-t border-ink-8 px-5 py-4"
                    >
                        <Link :href="route('fornecedores.edit', fornecedor.id)">
                            <Button label="Editar cadastro" severity="secondary" outlined size="small">
                                <template #icon><Icone nome="edit" :tamanho="16" /></template>
                            </Button>
                        </Link>
                    </div>
                </div>

                <!-- Contratos -->
                <div
                    class="mb-4 overflow-hidden rounded-lg border border-ink-8 bg-white shadow-card"
                >
                    <div class="border-b border-ink-8 px-5 py-4">
                        <h2
                            class="text-[12px] font-semibold uppercase tracking-[0.06em] text-ink-55"
                        >
                            Contratos
                        </h2>
                    </div>

                    <DataTable
                        :value="contratos"
                        data-key="id"
                        size="small"
                        :row-class="classeContrato"
                        @row-click="abrirContrato"
                    >
                        <template #empty>
                            <p class="py-10 text-center text-[13px] text-ink-55">
                                Nenhum contrato cadastrado para este fornecedor.
                            </p>
                        </template>

                        <Column field="descricao" header="Descrição">
                            <template #body="{ data }">
                                <div class="font-semibold text-ink">{{ data.descricao }}</div>
                                <div v-if="data.categoria" class="mt-0.5 text-[12px] text-ink-55">
                                    {{ data.categoria.nome }}
                                </div>
                            </template>
                        </Column>

                        <Column field="tipo" header="Tipo">
                            <template #body="{ data }">
                                {{ data.tipo === 'recorrente' ? 'Recorrente' : 'Pontual' }}
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

                <!-- Últimos pagamentos -->
                <div class="overflow-hidden rounded-lg border border-ink-8 bg-white shadow-card">
                    <div class="border-b border-ink-8 px-5 py-4">
                        <h2
                            class="text-[12px] font-semibold uppercase tracking-[0.06em] text-ink-55"
                        >
                            Últimos pagamentos
                        </h2>
                    </div>

                    <DataTable
                        :value="pagamentos"
                        data-key="id"
                        size="small"
                        :row-class="classePagamento"
                        @row-click="abrirPagamento"
                    >
                        <template #empty>
                            <p class="py-10 text-center text-[13px] text-ink-55">
                                Nenhum pagamento lançado para este fornecedor.
                            </p>
                        </template>

                        <Column field="descricao" header="Descrição">
                            <template #body="{ data }">
                                <span class="font-semibold text-ink">{{ data.descricao }}</span>
                            </template>
                        </Column>

                        <Column field="categoria" header="Categoria">
                            <template #body="{ data }">
                                {{ data.categoria?.nome ?? '—' }}
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

            <ContasBancarias
                tipo-beneficiario="fornecedor"
                :beneficiario-id="fornecedor.id"
                :contas="fornecedor.contas_bancarias ?? []"
                :opcoes="opcoes"
            />
        </div>
    </AuthenticatedLayout>
</template>
