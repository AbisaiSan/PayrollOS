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
import type { Fornecedor, Opcao, Paginado } from '@/types';

type LinhaFornecedor = Fornecedor & {
    contratos_count: number;
    contas_ativas_count: number;
};

const props = defineProps<{
    fornecedores: Paginado<LinhaFornecedor>;
    filtros: Record<string, string | undefined>;
    opcoes: { status: Opcao[]; tipoFornecedor: Opcao[] };
}>();

const { formatarDocumento } = useFormato();
const { pode } = usePermissoes();

const busca = ref(props.filtros.busca ?? '');
const status = ref(props.filtros.status ?? null);
const tipoFornecedor = ref(props.filtros.tipo_fornecedor ?? null);

const TIPO_FORNECEDOR: Record<string, string> = {
    produto: 'Fornecedor de produto',
    servico: 'Prestador de serviço',
    ambos: 'Ambos',
};

const temFiltro = computed(() => !!(busca.value || status.value || tipoFornecedor.value));

const { carregando, erro, consultar: visitar, tentarNovamente } = useConsulta(
    route('fornecedores.index'),
);

const consultar = (extras: Record<string, unknown> = {}) => {
    visitar({
        busca: busca.value || undefined,
        status: status.value || undefined,
        tipo_fornecedor: tipoFornecedor.value || undefined,
        ...extras,
    });
};

// Debounce só na busca por texto; os selects aplicam na hora.
const consultarComDebounce = debounce(() => consultar(), 350);

watch(busca, () => consultarComDebounce());
watch([status, tipoFornecedor], () => consultar());

const limparFiltros = () => {
    busca.value = '';
    status.value = null;
    tipoFornecedor.value = null;
};

const mudarPagina = (evento: { page: number; rows: number }) => {
    consultar({ page: evento.page + 1, por_pagina: evento.rows });
};

const abrir = (evento: { data: LinhaFornecedor }) => {
    router.get(route('fornecedores.show', evento.data.id));
};

const irParaCadastro = () => router.get(route('fornecedores.create'));

const classeLinha = () => 'cursor-pointer';
</script>

<template>
    <Head title="Fornecedores" />

    <AuthenticatedLayout>
        <template #header>
            <CabecalhoPagina
                titulo="Fornecedores"
                descricao="Prestadores de serviço e fornecedores de produto"
            >
                <template #acoes>
                    <Link v-if="pode('fornecedores.gerenciar')" :href="route('fornecedores.create')">
                        <Button label="Novo fornecedor" size="small">
                            <template #icon><Icone nome="plus" :tamanho="16" /></template>
                        </Button>
                    </Link>
                </template>
            </CabecalhoPagina>
        </template>

        <!-- Filtros -->
        <div class="mb-4 flex flex-wrap items-center gap-2.5">
            <span class="relative min-w-[280px] flex-1 sm:max-w-md">
                <Icone
                    nome="search"
                    :tamanho="15"
                    class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-ink-35"
                />
                <InputText
                    v-model="busca"
                    placeholder="Buscar por razão social, nome fantasia ou documento…"
                    class="w-full !pl-9"
                    size="small"
                    aria-label="Buscar por razão social, nome fantasia ou documento"
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
                v-model="tipoFornecedor"
                :options="opcoes.tipoFornecedor"
                option-label="label"
                option-value="value"
                placeholder="Tipo — todos"
                show-clear
                size="small"
                class="w-[210px]"
                aria-label="Filtrar por tipo de fornecedor"
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
            <TabelaEsqueleto v-if="carregando" :colunas="5" />

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
                :value="fornecedores.data"
                data-key="id"
                size="small"
                lazy
                paginator
                :rows="fornecedores.per_page"
                :total-records="fornecedores.total"
                :first="(fornecedores.current_page - 1) * fornecedores.per_page"
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
                        titulo="Nenhum fornecedor encontrado para estes filtros"
                        descricao="Ajuste os filtros aplicados ou limpe-os para ver todo o cadastro."
                        acao="Limpar filtros"
                        @acao="limparFiltros"
                    />
                    <EstadoListagem
                        v-else
                        variante="vazio"
                        icone="briefcase"
                        titulo="Nenhum fornecedor cadastrado ainda"
                        descricao="Cadastre prestadores de serviço e fornecedores de produto para lançar pagamentos e contratos."
                        :acao="pode('fornecedores.gerenciar') ? 'Cadastrar o primeiro fornecedor' : undefined"
                        @acao="irParaCadastro"
                    />
                </template>

                <Column field="razao_social" header="Razão social">
                    <template #body="{ data }">
                        <div class="font-semibold text-ink">{{ data.razao_social }}</div>
                        <div class="mt-0.5 text-[12px] text-ink-55">
                            <span class="mono">{{ formatarDocumento(data.documento) }}</span>
                            <template v-if="data.nome_fantasia">
                                · {{ data.nome_fantasia }}
                            </template>
                        </div>
                    </template>
                </Column>

                <Column field="tipo_fornecedor" header="Tipo">
                    <template #body="{ data }">
                        {{ TIPO_FORNECEDOR[data.tipo_fornecedor] ?? data.tipo_fornecedor }}
                    </template>
                </Column>

                <Column
                    field="contratos_count"
                    header="Contratos"
                    class="text-right"
                    header-class="!text-right"
                >
                    <template #body="{ data }">
                        <span
                            class="mono inline-block rounded-full bg-ink-8 px-2 py-0.5 text-[12.25px] font-semibold text-ink-70"
                        >
                            {{ data.contratos_count }}
                        </span>
                    </template>
                </Column>

                <!-- Zero contas ativas em vermelho: sem destino não há como pagar. -->
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
                                    ? 'Sem conta ativa — não há destino para o pagamento'
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
