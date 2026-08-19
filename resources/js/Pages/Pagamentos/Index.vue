<script setup lang="ts">
import { ref, watch } from 'vue';
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
import { debounce } from '@/Composables/useDebounce';
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

const aplicarFiltros = () => {
    router.get(
        route('pagamentos.index'),
        {
            busca: busca.value || undefined,
            status: status.value || undefined,
            categoria_id: categoriaId.value || undefined,
            inicio: paraIso(inicio.value) ?? undefined,
            fim: paraIso(fim.value) ?? undefined,
        },
        { preserveState: true, replace: true },
    );
};

watch(busca, debounce(aplicarFiltros, 350));
watch([status, categoriaId, inicio, fim], aplicarFiltros);

const mudarPagina = (evento: { page: number; rows: number }) => {
    router.get(
        route('pagamentos.index'),
        { ...props.filtros, page: evento.page + 1, por_pagina: evento.rows },
        { preserveState: true, replace: true },
    );
};

/** Destaca a linha que venceu mas ainda nao foi promovida pela rotina diaria. */
const vencido = (linha: LinhaPagamento) => {
    const dias = diasAte(linha.data_vencimento);

    return dias !== null && dias < 0 && linha.status !== 'pago' && linha.status !== 'cancelado';
};
</script>

<template>
    <Head title="Pagamentos" />

    <AuthenticatedLayout>
        <template #header>
            <CabecalhoPagina
                titulo="Pagamentos"
                :descricao="`${pagamentos.total} lançamento(s)`"
            >
                <template #acoes>
                    <Link v-if="pode('pagamentos.gerenciar')" :href="route('pagamentos.create')">
                        <Button label="Novo lançamento" icon="pi pi-plus" size="small" />
                    </Link>
                </template>
            </CabecalhoPagina>
        </template>

        <div class="space-y-4">
            <div class="grid gap-4 sm:grid-cols-2">
                <div class="rounded-xl border border-black/5 bg-white px-5 py-4">
                    <p class="text-xs font-medium uppercase tracking-wide text-corebanx-preto/40">
                        Total em aberto
                    </p>
                    <p class="mt-1 text-xl font-semibold tabular-nums text-corebanx-preto">
                        {{ formatarMoeda(totais.emAberto) }}
                    </p>
                </div>
                <div
                    class="rounded-xl border bg-white px-5 py-4"
                    :class="totais.atrasado > 0 ? 'border-red-200' : 'border-black/5'"
                >
                    <p class="text-xs font-medium uppercase tracking-wide text-corebanx-preto/40">
                        Atrasado
                    </p>
                    <p
                        class="mt-1 text-xl font-semibold tabular-nums"
                        :class="totais.atrasado > 0 ? 'text-red-600' : 'text-corebanx-preto'"
                    >
                        {{ formatarMoeda(totais.atrasado) }}
                    </p>
                </div>
            </div>

            <div class="flex flex-wrap gap-3 rounded-xl border border-black/5 bg-white p-4">
                <InputText
                    v-model="busca"
                    placeholder="Buscar pela descrição"
                    class="min-w-56 flex-1"
                    size="small"
                />
                <Select
                    v-model="status"
                    :options="opcoes.status"
                    option-label="label"
                    option-value="value"
                    placeholder="Status"
                    show-clear
                    size="small"
                    class="w-40"
                />
                <Select
                    v-model="categoriaId"
                    :options="opcoes.categorias"
                    option-label="nome"
                    option-value="id"
                    placeholder="Categoria"
                    show-clear
                    size="small"
                    class="w-48"
                />
                <DatePicker
                    v-model="inicio"
                    date-format="dd/mm/yy"
                    placeholder="Vence de"
                    show-button-bar
                    size="small"
                    class="w-40"
                />
                <DatePicker
                    v-model="fim"
                    date-format="dd/mm/yy"
                    placeholder="Vence até"
                    show-button-bar
                    size="small"
                    class="w-40"
                />
            </div>

            <div class="overflow-hidden rounded-xl border border-black/5 bg-white">
                <DataTable
                    :value="pagamentos.data"
                    data-key="id"
                    size="small"
                    lazy
                    paginator
                    :rows="pagamentos.per_page"
                    :total-records="pagamentos.total"
                    :first="(pagamentos.current_page - 1) * pagamentos.per_page"
                    :rows-per-page-options="[20, 50, 100]"
                    :row-class="(linha) => (vencido(linha) ? 'bg-red-50/60' : '')"
                    @page="mudarPagina"
                >
                    <template #empty>
                        <p class="py-10 text-center text-sm text-corebanx-preto/45">
                            Nenhum lançamento encontrado.
                        </p>
                    </template>

                    <Column field="descricao" header="Descrição">
                        <template #body="{ data }">
                            <Link
                                :href="route('pagamentos.show', data.id)"
                                class="font-medium text-corebanx-preto hover:text-corebanx-laranja"
                            >
                                {{ data.descricao }}
                            </Link>
                            <p class="text-xs text-corebanx-preto/50">
                                {{ data.beneficiario_nome }}
                            </p>
                        </template>
                    </Column>

                    <Column field="categoria" header="Categoria">
                        <template #body="{ data }">
                            {{ data.categoria ?? '—' }}
                            <p v-if="data.competencia" class="text-xs text-corebanx-preto/50">
                                {{ formatarCompetencia(data.competencia) }}
                            </p>
                        </template>
                    </Column>

                    <Column field="data_vencimento" header="Vencimento">
                        <template #body="{ data }">
                            <span class="tabular-nums">
                                {{ formatarData(data.data_vencimento) }}
                            </span>
                        </template>
                    </Column>

                    <Column field="valor" header="Valor">
                        <template #body="{ data }">
                            <span class="font-medium tabular-nums">
                                {{ formatarMoeda(data.valor) }}
                            </span>
                        </template>
                    </Column>

                    <Column field="forma_pagamento" header="Forma" />

                    <Column field="status" header="Status">
                        <template #body="{ data }">
                            <StatusBadge :status="data.status" />
                            <p v-if="data.data_pagamento" class="mt-0.5 text-xs text-corebanx-preto/50">
                                {{ formatarData(data.data_pagamento) }}
                            </p>
                        </template>
                    </Column>
                </DataTable>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
