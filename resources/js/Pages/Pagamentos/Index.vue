<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import InputText from 'primevue/inputtext';
import Select from 'primevue/select';
import DatePicker from 'primevue/datepicker';
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
import type { Opcao, Paginado } from '@/types';

interface LinhaPagamento {
    id: number;
    descricao: string;
    beneficiario_nome: string;
    beneficiario_tipo: string;
    categoria: string | null;
    competencia: string | null;
    valor: string;
    data_vencimento: string;
    data_pagamento: string | null;
    forma_pagamento: string;
    status: string;
    conta_destino: string | null;
}

const props = defineProps<{
    pagamentos: Paginado<LinhaPagamento>;
    filtros: Record<string, string | undefined>;
    opcoes: { status: Opcao[]; categorias: Array<{ id: number; nome: string }> };
    totais: { emAberto: number; atrasado: number };
}>();

const { formatarMoeda, formatarData, formatarCompetencia, diasAte, paraDate, paraIso } =
    useFormato();
const { pode } = usePermissoes();

const busca = ref(props.filtros.busca ?? '');
const status = ref(props.filtros.status ?? null);
const categoriaId = ref(props.filtros.categoria_id ?? null);
const inicio = ref<Date | null>(paraDate(props.filtros.inicio));
const fim = ref<Date | null>(paraDate(props.filtros.fim));

const FORMA_PAGAMENTO: Record<string, string> = {
    pix: 'Pix',
    ted: 'TED',
    boleto: 'Boleto',
    dinheiro: 'Dinheiro',
    outro: 'Outro',
};

const temFiltro = computed(
    () => !!(busca.value || status.value || categoriaId.value || inicio.value || fim.value),
);

const { carregando, erro, consultar: visitar, tentarNovamente } = useConsulta(
    route('pagamentos.index'),
);

const consultar = (extras: Record<string, unknown> = {}) => {
    visitar({
        busca: busca.value || undefined,
        status: status.value || undefined,
        categoria_id: categoriaId.value || undefined,
        inicio: paraIso(inicio.value) ?? undefined,
        fim: paraIso(fim.value) ?? undefined,
        ...extras,
    });
};

// Debounce só na busca por texto; os demais controles aplicam na hora.
// O callback ignora o argumento do watch de propósito: ele traz o novo valor do
// ref, que não tem nada a ver com os extras de query de consultar().
const consultarComDebounce = debounce(() => consultar(), 350);

watch(busca, () => consultarComDebounce());
watch([status, categoriaId, inicio, fim], () => consultar());

const limparFiltros = () => {
    busca.value = '';
    status.value = null;
    categoriaId.value = null;
    inicio.value = null;
    fim.value = null;
};

const mudarPagina = (evento: { page: number; rows: number }) => {
    consultar({ page: evento.page + 1, por_pagina: evento.rows });
};

const abrir = (evento: { data: LinhaPagamento }) => {
    router.get(route('pagamentos.show', evento.data.id));
};

/**
 * Vencido e ainda não confirmado. A promoção para "Atrasado" só roda às 6h15, então
 * entre o vencimento e a rotina a linha ainda diz "Pendente" mesmo já vencida —
 * sem este realce o atraso fica invisível por até um dia.
 */
const linhaVencida = (linha: LinhaPagamento) => {
    if (linha.status === 'atrasado') {
        return true;
    }

    const dias = diasAte(linha.data_vencimento);

    return dias !== null && dias < 0 && !['pago', 'cancelado'].includes(linha.status);
};

const irParaCadastro = () => router.get(route('pagamentos.create'));

const classeLinha = (linha: LinhaPagamento) => ({
    'cursor-pointer': true,
    'bg-perigo-bg hover:!bg-[#F7DCD9]': linhaVencida(linha),
});
</script>

<template>
    <Head title="Pagamentos" />

    <AuthenticatedLayout>
        <template #header>
            <CabecalhoPagina
                titulo="Pagamentos"
                descricao="Folha, fornecedores, prestadores e reembolsos em um único fluxo de controle"
            >
                <template #acoes>
                    <Link v-if="pode('relatorios.ver')" :href="route('relatorios.index')">
                        <Button label="Exportar" severity="secondary" outlined size="small">
                            <template #icon><Icone nome="download" :tamanho="16" /></template>
                        </Button>
                    </Link>
                    <Link v-if="pode('pagamentos.gerenciar')" :href="route('pagamentos.create')">
                        <Button label="Lançar pagamento" size="small">
                            <template #icon><Icone nome="plus" :tamanho="16" /></template>
                        </Button>
                    </Link>
                </template>
            </CabecalhoPagina>
        </template>

        <!-- Totalizadores -->
        <div class="mb-4 flex flex-wrap gap-2.5">
            <div
                class="flex items-center gap-2.5 rounded-md border border-ink-8 bg-white px-4 py-2.5 shadow-card"
            >
                <span class="text-[11.5px] text-ink-55">Em aberto</span>
                <span class="mono text-[15px] font-bold">{{ formatarMoeda(totais.emAberto) }}</span>
            </div>
            <div
                class="flex items-center gap-2.5 rounded-md border px-4 py-2.5 shadow-card"
                :class="
                    totais.atrasado > 0
                        ? 'border-perigo-line bg-perigo-bg'
                        : 'border-ink-8 bg-white'
                "
            >
                <span
                    class="text-[11.5px]"
                    :class="totais.atrasado > 0 ? 'text-perigo' : 'text-ink-55'"
                >
                    Atrasado
                </span>
                <span
                    class="mono text-[15px] font-bold"
                    :class="totais.atrasado > 0 ? 'text-perigo' : ''"
                >
                    {{ formatarMoeda(totais.atrasado) }}
                </span>
            </div>
        </div>

        <!-- Filtros -->
        <div class="mb-4 flex flex-wrap items-center gap-2.5">
            <span class="relative min-w-[230px] flex-1 sm:max-w-xs">
                <Icone
                    nome="search"
                    :tamanho="15"
                    class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-ink-35"
                />
                <InputText
                    v-model="busca"
                    placeholder="Buscar por descrição…"
                    class="w-full !pl-9"
                    size="small"
                    aria-label="Buscar por descrição"
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
                v-model="categoriaId"
                :options="opcoes.categorias"
                option-label="nome"
                option-value="id"
                placeholder="Categoria — todas"
                show-clear
                size="small"
                class="w-[190px]"
                aria-label="Filtrar por categoria"
            />

            <DatePicker
                v-model="inicio"
                date-format="dd/mm/yy"
                placeholder="Vence de"
                show-button-bar
                show-icon
                icon-display="input"
                size="small"
                class="w-[152px]"
                aria-label="Vence de"
            />

            <DatePicker
                v-model="fim"
                date-format="dd/mm/yy"
                placeholder="Vence até"
                show-button-bar
                show-icon
                icon-display="input"
                size="small"
                class="w-[152px]"
                aria-label="Vence até"
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
                :value="pagamentos.data"
                data-key="id"
                size="small"
                lazy
                paginator
                :rows="pagamentos.per_page"
                :total-records="pagamentos.total"
                :first="(pagamentos.current_page - 1) * pagamentos.per_page"
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
                        titulo="Nenhum lançamento encontrado para estes filtros"
                        descricao="Ajuste os filtros aplicados ou limpe-os para ver todos os lançamentos."
                        acao="Limpar filtros"
                        @acao="limparFiltros"
                    />
                    <EstadoListagem
                        v-else
                        variante="vazio"
                        icone="wallet"
                        titulo="Nenhum pagamento cadastrado ainda"
                        descricao="Comece cadastrando colaboradores e fornecedores, depois lance o primeiro pagamento."
                        :acao="pode('pagamentos.gerenciar') ? 'Lançar o primeiro pagamento' : undefined"
                        @acao="irParaCadastro"
                    />
                </template>

                <Column field="descricao" header="Descrição">
                    <template #body="{ data }">
                        <div class="font-semibold text-ink">{{ data.descricao }}</div>
                        <div class="mt-0.5 text-[12px] text-ink-55">
                            {{ data.beneficiario_nome }}
                        </div>
                    </template>
                </Column>

                <Column field="categoria" header="Categoria">
                    <template #body="{ data }">
                        {{ data.categoria ?? '—' }}
                        <div v-if="data.competencia" class="mono mt-0.5 text-[12px] text-ink-55">
                            {{ formatarCompetencia(data.competencia) }}
                        </div>
                    </template>
                </Column>

                <Column field="data_vencimento" header="Vencimento">
                    <template #body="{ data }">
                        <span class="mono">{{ formatarData(data.data_vencimento) }}</span>
                    </template>
                </Column>

                <Column field="valor" header="Valor" class="text-right" header-class="!text-right">
                    <template #body="{ data }">
                        <span class="mono font-semibold">{{ formatarMoeda(data.valor) }}</span>
                    </template>
                </Column>

                <Column field="forma_pagamento" header="Forma">
                    <template #body="{ data }">
                        {{ FORMA_PAGAMENTO[data.forma_pagamento] ?? data.forma_pagamento }}
                    </template>
                </Column>

                <Column field="status" header="Status">
                    <template #body="{ data }">
                        <StatusBadge :status="data.status" />
                        <div v-if="data.data_pagamento" class="mono mt-1 text-[12px] text-ink-55">
                            {{ formatarData(data.data_pagamento) }}
                        </div>
                    </template>
                </Column>
            </DataTable>
        </div>
    </AuthenticatedLayout>
</template>
