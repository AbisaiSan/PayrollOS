<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Select from 'primevue/select';
import DatePicker from 'primevue/datepicker';
import Button from 'primevue/button';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import CabecalhoPagina from '@/Components/CabecalhoPagina.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import Icone from '@/Components/Icone.vue';
import EstadoListagem from '@/Components/EstadoListagem.vue';
import TabelaEsqueleto from '@/Components/TabelaEsqueleto.vue';
import { useConsulta } from '@/Composables/useConsulta';
import { useFormato } from '@/Composables/useFormato';
import { usePermissoes } from '@/Composables/usePermissoes';
import type { Opcao, Paginado } from '@/types';

interface LinhaReembolso {
    id: number;
    descricao: string;
    colaborador_nome: string;
    colaborador_departamento: string;
    categoria: string;
    valor: string;
    data_solicitacao: string;
    status: string;
}

interface ColaboradorOpcao {
    id: number;
    nome: string;
    departamento: string;
}

const props = defineProps<{
    reembolsos: Paginado<LinhaReembolso>;
    filtros: Record<string, string | undefined>;
    opcoes: { status: Opcao[]; categorias: Opcao[]; colaboradores: ColaboradorOpcao[] };
}>();

const { formatarMoeda, formatarData, paraDate, paraIso } = useFormato();
const { podeAlguma } = usePermissoes();

const status = ref(props.filtros.status ?? null);
const categoria = ref(props.filtros.categoria ?? null);
const colaboradorId = ref(props.filtros.colaborador_id ?? null);
const inicio = ref<Date | null>(paraDate(props.filtros.inicio));
const fim = ref<Date | null>(paraDate(props.filtros.fim));

const temFiltro = computed(
    () => !!(status.value || categoria.value || colaboradorId.value || inicio.value || fim.value),
);

const { carregando, erro, consultar: visitar, tentarNovamente } = useConsulta(
    route('reembolsos.index'),
);

const consultar = (extras: Record<string, unknown> = {}) => {
    visitar({
        status: status.value || undefined,
        categoria: categoria.value || undefined,
        colaborador_id: colaboradorId.value || undefined,
        inicio: paraIso(inicio.value) ?? undefined,
        fim: paraIso(fim.value) ?? undefined,
        ...extras,
    });
};

// Todos os controles são de escolha, não de digitação: aplicam na hora, sem debounce.
watch([status, categoria, colaboradorId, inicio, fim], () => consultar());

const limparFiltros = () => {
    status.value = null;
    categoria.value = null;
    colaboradorId.value = null;
    inicio.value = null;
    fim.value = null;
};

const mudarPagina = (evento: { page: number; rows: number }) => {
    consultar({ page: evento.page + 1, por_pagina: evento.rows });
};

const abrir = (evento: { data: LinhaReembolso }) => {
    router.get(route('reembolsos.show', evento.data.id));
};

const irParaCadastro = () => router.get(route('reembolsos.create'));

const classeLinha = () => 'cursor-pointer';
</script>

<template>
    <Head title="Reembolsos" />

    <AuthenticatedLayout>
        <template #header>
            <CabecalhoPagina
                titulo="Reembolsos"
                descricao="Solicitações de colaboradores, aprovação e registro de pagamento"
            >
                <template #acoes>
                    <Link
                        v-if="podeAlguma(['reembolsos.gerenciar', 'reembolsos.solicitar'])"
                        :href="route('reembolsos.create')"
                    >
                        <Button label="Nova solicitação" size="small">
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
                v-model="categoria"
                :options="opcoes.categorias"
                option-label="label"
                option-value="value"
                placeholder="Categoria — todas"
                show-clear
                size="small"
                class="w-[180px]"
                aria-label="Filtrar por categoria"
            />

            <Select
                v-model="colaboradorId"
                :options="opcoes.colaboradores"
                option-label="nome"
                option-value="id"
                placeholder="Colaborador — todos"
                show-clear
                filter
                filter-placeholder="Buscar colaborador…"
                size="small"
                class="w-[232px]"
                aria-label="Filtrar por colaborador"
            >
                <template #option="{ option }">
                    <div>
                        <div>{{ option.nome }}</div>
                        <div class="text-[12px] text-ink-55">{{ option.departamento }}</div>
                    </div>
                </template>
            </Select>

            <DatePicker
                v-model="inicio"
                date-format="dd/mm/yy"
                placeholder="Solicitado de"
                show-button-bar
                show-icon
                icon-display="input"
                size="small"
                class="w-[164px]"
                aria-label="Solicitado de"
            />

            <DatePicker
                v-model="fim"
                date-format="dd/mm/yy"
                placeholder="Solicitado até"
                show-button-bar
                show-icon
                icon-display="input"
                size="small"
                class="w-[164px]"
                aria-label="Solicitado até"
            />

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
            <TabelaEsqueleto v-if="carregando" :colunas="6" />

            <EstadoListagem
                v-else-if="erro"
                variante="erro"
                titulo="Não foi possível carregar os dados"
                descricao="Verifique sua conexão e tente novamente. Se o problema continuar, contate o suporte."
                acao="Tentar novamente"
                @acao="tentarNovamente"
            />

            <DataTable
                v-else
                :value="reembolsos.data"
                data-key="id"
                size="small"
                lazy
                paginator
                :rows="reembolsos.per_page"
                :total-records="reembolsos.total"
                :first="(reembolsos.current_page - 1) * reembolsos.per_page"
                :rows-per-page-options="[20, 50, 100]"
                :row-class="classeLinha"
                paginator-template="CurrentPageReport PrevPageLink PageLinks NextPageLink RowsPerPageDropdown"
                current-page-report-template="Mostrando {first}–{last} de {totalRecords}"
                @page="mudarPagina"
                @row-click="abrir"
            >
                <template #empty>
                    <EstadoListagem
                        v-if="temFiltro"
                        variante="vazio-filtro"
                        titulo="Nenhuma solicitação encontrada para estes filtros"
                        descricao="Ajuste os filtros aplicados ou limpe-os para ver todas as solicitações."
                        acao="Limpar filtros"
                        @acao="limparFiltros"
                    />
                    <EstadoListagem
                        v-else
                        variante="vazio"
                        icone="receipt"
                        titulo="Nenhuma solicitação de reembolso ainda"
                        descricao="Quando alguém adiantar uma despesa, registre a solicitação aqui para ela entrar na fila de aprovação."
                        :acao="
                            podeAlguma(['reembolsos.gerenciar', 'reembolsos.solicitar'])
                                ? 'Nova solicitação'
                                : undefined
                        "
                        @acao="irParaCadastro"
                    />
                </template>

                <Column field="descricao" header="Descrição">
                    <template #body="{ data }">
                        <div class="font-semibold text-ink">{{ data.descricao }}</div>
                        <div class="mono mt-0.5 text-[12px] text-ink-55">#{{ data.id }}</div>
                    </template>
                </Column>

                <Column field="colaborador_nome" header="Colaborador">
                    <template #body="{ data }">
                        {{ data.colaborador_nome }}
                        <div class="mt-0.5 text-[12px] text-ink-55">
                            {{ data.colaborador_departamento }}
                        </div>
                    </template>
                </Column>

                <Column field="categoria" header="Categoria" />

                <Column field="data_solicitacao" header="Solicitação">
                    <template #body="{ data }">
                        <span class="mono">{{ formatarData(data.data_solicitacao) }}</span>
                    </template>
                </Column>

                <Column field="valor" header="Valor" class="text-right" header-class="!text-right">
                    <template #body="{ data }">
                        <span class="mono font-semibold">{{ formatarMoeda(data.valor) }}</span>
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
