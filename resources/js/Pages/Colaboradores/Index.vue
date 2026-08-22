<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import InputText from 'primevue/inputtext';
import Select from 'primevue/select';
import Button from 'primevue/button';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import CabecalhoPagina from '@/Components/CabecalhoPagina.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import Icone from '@/Components/Icone.vue';
import EstadoListagem from '@/Components/EstadoListagem.vue';
import TabelaEsqueleto from '@/Components/TabelaEsqueleto.vue';
import { debounce } from '@/Composables/useDebounce';
import { useConsulta } from '@/Composables/useConsulta';
import { useFormato } from '@/Composables/useFormato';
import { usePermissoes } from '@/Composables/usePermissoes';
import type { Colaborador, Opcao, Paginado } from '@/types';

type LinhaColaborador = Colaborador & { contas_ativas_count: number };

const props = defineProps<{
    colaboradores: Paginado<LinhaColaborador>;
    filtros: Record<string, string | undefined>;
    opcoes: { status: Opcao[]; departamentos: string[] };
}>();

const { formatarMoeda, formatarDocumento, formatarData } = useFormato();
const { pode } = usePermissoes();

const busca = ref(props.filtros.busca ?? '');
const status = ref(props.filtros.status ?? null);
const departamento = ref(props.filtros.departamento ?? null);

const TIPO_CONTRATO: Record<string, string> = {
    clt: 'CLT',
    pj: 'PJ',
    estagio: 'Estágio',
    autonomo: 'Autônomo',
};

const temFiltro = computed(() => !!(busca.value || status.value || departamento.value));

const { carregando, erro, consultar: visitar, tentarNovamente } = useConsulta(
    route('colaboradores.index'),
);

const consultar = (extras: Record<string, unknown> = {}) => {
    visitar({
        busca: busca.value || undefined,
        status: status.value || undefined,
        departamento: departamento.value || undefined,
        ...extras,
    });
};

// Debounce só na busca por texto; os selects aplicam na hora.
const consultarComDebounce = debounce(() => consultar(), 350);

watch(busca, () => consultarComDebounce());
watch([status, departamento], () => consultar());

const limparFiltros = () => {
    busca.value = '';
    status.value = null;
    departamento.value = null;
};

const mudarPagina = (evento: { page: number; rows: number }) => {
    consultar({ page: evento.page + 1, por_pagina: evento.rows });
};

const abrir = (evento: { data: LinhaColaborador }) => {
    router.get(route('colaboradores.show', evento.data.id));
};

const irParaCadastro = () => router.get(route('colaboradores.create'));

const classeLinha = () => 'cursor-pointer';
</script>

<template>
    <Head title="Colaboradores" />

    <AuthenticatedLayout>
        <template #header>
            <CabecalhoPagina
                titulo="Colaboradores"
                descricao="Folha de pagamento — cadastro e contas de destino"
            >
                <template #acoes>
                    <Link
                        v-if="pode('colaboradores.gerenciar')"
                        :href="route('colaboradores.create')"
                    >
                        <Button label="Novo colaborador" size="small">
                            <template #icon><Icone nome="plus" :tamanho="16" /></template>
                        </Button>
                    </Link>
                </template>
            </CabecalhoPagina>
        </template>

        <!-- Filtros -->
        <div class="mb-4 flex flex-wrap items-center gap-2.5">
            <span class="relative min-w-[260px] flex-1 sm:max-w-sm">
                <Icone
                    nome="search"
                    :tamanho="15"
                    class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-ink-35"
                />
                <InputText
                    v-model="busca"
                    placeholder="Buscar por nome, CPF, cargo ou e-mail…"
                    class="w-full !pl-9"
                    size="small"
                    aria-label="Buscar por nome, CPF, cargo ou e-mail"
                />
            </span>

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
                v-model="departamento"
                :options="opcoes.departamentos"
                placeholder="Departamento — todos"
                show-clear
                size="small"
                class="w-[200px]"
                aria-label="Filtrar por departamento"
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
            <TabelaEsqueleto v-if="carregando" :colunas="7" />

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
                :value="colaboradores.data"
                data-key="id"
                size="small"
                lazy
                paginator
                :rows="colaboradores.per_page"
                :total-records="colaboradores.total"
                :first="(colaboradores.current_page - 1) * colaboradores.per_page"
                :rows-per-page-options="[15, 30, 50]"
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
                        titulo="Nenhum colaborador encontrado para estes filtros"
                        descricao="Ajuste os filtros aplicados ou limpe-os para ver todo o cadastro."
                        acao="Limpar filtros"
                        @acao="limparFiltros"
                    />
                    <EstadoListagem
                        v-else
                        variante="vazio"
                        icone="users"
                        titulo="Nenhum colaborador cadastrado ainda"
                        descricao="O cadastro do colaborador e a conta de destino dele são o que permite lançar folha."
                        :acao="pode('colaboradores.gerenciar') ? 'Cadastrar o primeiro colaborador' : undefined"
                        @acao="irParaCadastro"
                    />
                </template>

                <Column field="nome" header="Nome">
                    <template #body="{ data }">
                        <div class="font-semibold text-ink">{{ data.nome }}</div>
                        <div class="mono mt-0.5 text-[12px] text-ink-55">
                            {{ formatarDocumento(data.cpf) }}
                        </div>
                    </template>
                </Column>

                <Column field="cargo" header="Cargo">
                    <template #body="{ data }">
                        {{ data.cargo }}
                        <div class="mt-0.5 text-[12px] text-ink-55">{{ data.departamento }}</div>
                    </template>
                </Column>

                <Column field="tipo_contrato" header="Contrato">
                    <template #body="{ data }">
                        {{ TIPO_CONTRATO[data.tipo_contrato] ?? data.tipo_contrato }}
                    </template>
                </Column>

                <Column field="data_admissao" header="Admissão">
                    <template #body="{ data }">
                        <span class="mono">{{ formatarData(data.data_admissao) }}</span>
                    </template>
                </Column>

                <Column
                    field="salario_base"
                    header="Salário base"
                    class="text-right"
                    header-class="!text-right"
                >
                    <template #body="{ data }">
                        <span class="mono font-semibold">{{
                            formatarMoeda(data.salario_base)
                        }}</span>
                    </template>
                </Column>

                <!--
                    Zero contas ativas fica em vermelho: sem destino não há como lançar
                    folha para essa pessoa, e é o tipo de pendência que passa despercebida
                    até o dia do pagamento.
                -->
                <Column
                    field="contas_ativas_count"
                    header="Contas"
                    class="text-right"
                    header-class="!text-right"
                >
                    <template #body="{ data }">
                        <span
                            class="mono inline-block rounded-full px-2 py-0.5 text-[12.25px] font-semibold"
                            :class="
                                data.contas_ativas_count === 0
                                    ? 'bg-perigo-bg text-perigo'
                                    : 'bg-ink-8 text-ink-70'
                            "
                            :title="
                                data.contas_ativas_count === 0
                                    ? 'Sem conta ativa — não há destino para a folha'
                                    : undefined
                            "
                        >
                            {{ data.contas_ativas_count }}
                        </span>
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
