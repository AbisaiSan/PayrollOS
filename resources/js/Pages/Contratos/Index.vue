<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Select from 'primevue/select';
import Button from 'primevue/button';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import CabecalhoPagina from '@/Components/CabecalhoPagina.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import Icone from '@/Components/Icone.vue';
import { useFormato } from '@/Composables/useFormato';
import { usePermissoes } from '@/Composables/usePermissoes';
import type { Opcao, Paginado } from '@/types';

interface LinhaContrato {
    id: number;
    descricao: string;
    categoria: string | null;
    fornecedor: string;
    tipo: string;
    tipo_rotulo: string;
    periodicidade_rotulo: string | null;
    dia_vencimento: number | null;
    valor: string;
    proximo_vencimento: string | null;
    status: string;
}

interface FornecedorOpcao {
    id: number;
    razao_social: string;
    nome_fantasia: string | null;
}

const props = defineProps<{
    contratos: Paginado<LinhaContrato>;
    filtros: Record<string, string | undefined>;
    opcoes: { status: Opcao[]; tipo: Opcao[]; fornecedores: FornecedorOpcao[] };
}>();

const { formatarMoeda, formatarData, vencimentoRelativo } = useFormato();
const { pode } = usePermissoes();

const status = ref(props.filtros.status ?? null);
const tipo = ref(props.filtros.tipo ?? null);
const fornecedorId = ref(props.filtros.fornecedor_id ?? null);

const temFiltro = computed(() => !!(status.value || tipo.value || fornecedorId.value));

const consultar = (extras: Record<string, unknown> = {}) => {
    router.get(
        route('contratos.index'),
        {
            status: status.value || undefined,
            tipo: tipo.value || undefined,
            fornecedor_id: fornecedorId.value || undefined,
            ...extras,
        },
        { preserveState: true, preserveScroll: true, replace: true },
    );
};

// Todos os controles são de escolha: aplicam na hora, sem debounce.
watch([status, tipo, fornecedorId], () => consultar());

const limparFiltros = () => {
    status.value = null;
    tipo.value = null;
    fornecedorId.value = null;
};

const mudarPagina = (evento: { page: number; rows: number }) => {
    consultar({ page: evento.page + 1, por_pagina: evento.rows });
};

const abrir = (evento: { data: LinhaContrato }) => {
    router.get(route('contratos.show', evento.data.id));
};

const classeLinha = () => 'cursor-pointer';

/**
 * A rotina das 6h só gera lançamento para contrato ativo. Num suspenso ou
 * encerrado a data seguinte é resíduo do que ficou gravado, e mostrá-la como se
 * fosse compromisso faria a tela prometer um lançamento que não vai nascer.
 */
const mostraProximo = (linha: LinhaContrato) =>
    linha.status === 'ativo' && !!linha.proximo_vencimento;
</script>

<template>
    <Head title="Contratos" />

    <AuthenticatedLayout>
        <template #header>
            <CabecalhoPagina
                titulo="Contratos"
                descricao="Vínculos com fornecedores — pontuais e recorrentes"
            >
                <template #acoes>
                    <Link v-if="pode('contratos.gerenciar')" :href="route('contratos.create')">
                        <Button label="Novo contrato" size="small">
                            <template #icon><Icone nome="plus" :tamanho="16" /></template>
                        </Button>
                    </Link>
                </template>
            </CabecalhoPagina>
        </template>

        <!-- Filtros -->
        <div class="mb-4 flex flex-wrap items-center gap-2.5">
            <Select
                v-model="status"
                :options="opcoes.status"
                option-label="label"
                option-value="value"
                placeholder="Status — todos"
                show-clear
                size="small"
                class="w-[168px]"
                aria-label="Filtrar por status"
            />

            <Select
                v-model="tipo"
                :options="opcoes.tipo"
                option-label="label"
                option-value="value"
                placeholder="Tipo — todos"
                show-clear
                size="small"
                class="w-[170px]"
                aria-label="Filtrar por tipo"
            />

            <Select
                v-model="fornecedorId"
                :options="opcoes.fornecedores"
                option-label="razao_social"
                option-value="id"
                placeholder="Fornecedor — todos"
                show-clear
                filter
                filter-placeholder="Buscar fornecedor…"
                size="small"
                class="w-[262px]"
                aria-label="Filtrar por fornecedor"
            >
                <template #option="{ option }">
                    <div>
                        <div>{{ option.razao_social }}</div>
                        <div v-if="option.nome_fantasia" class="text-[12px] text-ink-55">
                            {{ option.nome_fantasia }}
                        </div>
                    </div>
                </template>
            </Select>

            <button
                v-if="temFiltro"
                type="button"
                class="flex items-center gap-1 px-1 py-1.5 text-[12.5px] font-semibold text-laranja-600 hover:underline"
                @click="limparFiltros"
            >
                <Icone nome="x" :tamanho="13" />
                Limpar filtros
            </button>
        </div>

        <!-- Grid -->
        <div class="overflow-hidden rounded-lg border border-ink-8 bg-white shadow-card">
            <DataTable
                :value="contratos.data"
                data-key="id"
                size="small"
                lazy
                paginator
                :rows="contratos.per_page"
                :total-records="contratos.total"
                :first="(contratos.current_page - 1) * contratos.per_page"
                :rows-per-page-options="[20, 50, 100]"
                :row-class="classeLinha"
                paginator-template="CurrentPageReport PrevPageLink PageLinks NextPageLink RowsPerPageDropdown"
                current-page-report-template="Mostrando {first}–{last} de {totalRecords}"
                @page="mudarPagina"
                @row-click="abrir"
            >
                <template #empty>
                    <p class="py-12 text-center text-[13px] text-ink-55">
                        {{
                            temFiltro
                                ? 'Nenhum contrato encontrado com esses filtros.'
                                : 'Nenhum contrato cadastrado ainda.'
                        }}
                    </p>
                </template>

                <Column field="descricao" header="Descrição">
                    <template #body="{ data }">
                        <div class="font-semibold text-ink">{{ data.descricao }}</div>
                        <div class="mt-0.5 text-[12px] text-ink-55">
                            {{ data.categoria ?? '—' }}
                        </div>
                    </template>
                </Column>

                <Column field="fornecedor" header="Fornecedor" />

                <Column field="tipo" header="Tipo">
                    <template #body="{ data }">
                        {{ data.tipo_rotulo }}
                        <div
                            v-if="data.periodicidade_rotulo"
                            class="mt-0.5 text-[12px] text-ink-55"
                        >
                            {{ data.periodicidade_rotulo }}<template v-if="data.dia_vencimento">
                                , dia <span class="mono">{{ data.dia_vencimento }}</span>
                            </template>
                        </div>
                    </template>
                </Column>

                <Column field="valor" header="Valor" class="text-right" header-class="!text-right">
                    <template #body="{ data }">
                        <span class="mono font-semibold">{{ formatarMoeda(data.valor) }}</span>
                    </template>
                </Column>

                <Column field="proximo_vencimento" header="Próximo vencimento">
                    <template #body="{ data }">
                        <template v-if="mostraProximo(data)">
                            <span class="mono">{{ formatarData(data.proximo_vencimento) }}</span>
                            <div class="mt-0.5 text-[12px] text-ink-55">
                                {{ vencimentoRelativo(data.proximo_vencimento) }}
                            </div>
                        </template>
                        <span v-else class="text-ink-35">—</span>
                    </template>
                </Column>

                <Column field="status" header="Status">
                    <template #body="{ data }">
                        <StatusBadge :status="data.status" />
                    </template>
                </Column>
            </DataTable>
        </div>
    </AuthenticatedLayout>
</template>
