<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Select from 'primevue/select';
import DatePicker from 'primevue/datepicker';
import Button from 'primevue/button';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import CabecalhoPagina from '@/Components/CabecalhoPagina.vue';
import CardIndicador from '@/Components/CardIndicador.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import Icone from '@/Components/Icone.vue';
import Aviso from '@/Components/Aviso.vue';
import ModalExportarRelatorio from '@/Components/ModalExportarRelatorio.vue';
import { useFormato } from '@/Composables/useFormato';
import { usePermissoes } from '@/Composables/usePermissoes';
import type { Opcao } from '@/types';

interface LinhaStatus {
    status: string;
    rotulo: string;
    total: number;
    quantidade: number;
    /** Falso em cancelado e rejeitado: aparecem na quebra, ficam fora do total. */
    realizavel: boolean;
}

interface LinhaCategoria {
    nome: string;
    total: number;
}

const props = defineProps<{
    filtros: {
        inicio: string;
        fim: string;
        categoria_id: number | null;
        status: string | null;
    };
    resumo: { total: number; quantidade: number; naoRealizavel: number };
    porStatus: LinhaStatus[];
    porCategoria: LinhaCategoria[];
    opcoes: { categorias: Array<{ id: number; nome: string }>; status: Opcao[] };
}>();

const { formatarMoeda, formatarData, paraDate, paraIso } = useFormato();
const { pode } = usePermissoes();

const inicio = ref<Date | null>(paraDate(props.filtros.inicio));
const fim = ref<Date | null>(paraDate(props.filtros.fim));
const categoriaId = ref(props.filtros.categoria_id);
const status = ref(props.filtros.status);

const consultar = () => {
    router.get(
        route('relatorios.index'),
        {
            inicio: paraIso(inicio.value) ?? undefined,
            fim: paraIso(fim.value) ?? undefined,
            categoria_id: categoriaId.value || undefined,
            status: status.value || undefined,
        },
        { preserveState: true, preserveScroll: true, replace: true },
    );
};

watch([inicio, fim, categoriaId, status], () => consultar());

/**
 * O backend cai no mês corrente quando não vem período, então "limpar" devolve
 * ao mês corrente em vez de a um intervalo vazio.
 */
const voltarAoMes = () => {
    inicio.value = null;
    fim.value = null;
    categoriaId.value = null;
    status.value = null;
};

const temRecorte = computed(() => !!(categoriaId.value || status.value));

const ticketMedio = computed(() =>
    props.resumo.quantidade > 0 ? props.resumo.total / props.resumo.quantidade : 0,
);

/**
 * Barra proporcional à maior categoria, não ao total — mesma escolha do
 * dashboard. Contra o total, a folha esmaga as demais e o gráfico não informa.
 */
const maiorCategoria = computed(() =>
    props.porCategoria.reduce((maior, linha) => Math.max(maior, linha.total), 0),
);

const proporcao = (total: number) =>
    maiorCategoria.value > 0 ? Math.round((total / maiorCategoria.value) * 100) : 0;

const modalExportar = ref(false);

/** Rótulos do recorte atual, para o modal dizer o que sairia no arquivo. */
const resumoFiltros = computed(() => ({
    categoria:
        props.opcoes.categorias.find((c) => c.id === props.filtros.categoria_id)?.nome ?? null,
    status: props.opcoes.status.find((s) => s.value === props.filtros.status)?.label ?? null,
}));
</script>

<template>
    <Head title="Relatórios" />

    <AuthenticatedLayout>
        <template #header>
            <CabecalhoPagina
                titulo="Relatórios"
                descricao="Resumo consolidado de pagamentos por status e categoria"
            >
                <template #acoes>
                    <Button
                        v-if="pode('relatorios.exportar')"
                        label="Exportar"
                        severity="secondary"
                        outlined
                        size="small"
                        @click="modalExportar = true"
                    >
                        <template #icon><Icone nome="download" :tamanho="16" /></template>
                    </Button>
                </template>
            </CabecalhoPagina>
        </template>

        <!-- Filtros -->
        <div class="mb-4 flex flex-wrap items-center gap-2.5">
            <DatePicker
                v-model="inicio"
                date-format="dd/mm/yy"
                placeholder="Período — de"
                show-icon
                icon-display="input"
                size="small"
                class="w-[168px]"
                aria-label="Período de"
            />

            <DatePicker
                v-model="fim"
                date-format="dd/mm/yy"
                placeholder="Período — até"
                :min-date="inicio ?? undefined"
                show-icon
                icon-display="input"
                size="small"
                class="w-[168px]"
                aria-label="Período até"
            />

            <Select
                v-model="categoriaId"
                :options="opcoes.categorias"
                option-label="nome"
                option-value="id"
                placeholder="Categoria — todas"
                show-clear
                size="small"
                class="w-[200px]"
                aria-label="Filtrar por categoria"
            />

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

            <button
                v-if="temRecorte"
                type="button"
                class="flex items-center gap-1 px-1 py-1.5 text-[12.5px] font-semibold text-laranja-600 hover:underline"
                @click="voltarAoMes"
            >
                <Icone nome="x" :tamanho="13" />
                Voltar ao mês corrente
            </button>
        </div>

        <!--
            O período é o recorte de tudo o que vem abaixo, e o backend arbitra o
            mês corrente quando ninguém escolhe. Dizer qual intervalo está em uso
            evita ler os números achando que são de outro.
        -->
        <p class="mb-4 text-[12px] text-ink-55">
            Período em uso:
            <span class="mono font-semibold text-ink-70">
                {{ formatarData(filtros.inicio) }} a {{ formatarData(filtros.fim) }}
            </span>
        </p>

        <!--
            O total exclui cancelado e rejeitado — dinheiro que não vai sair. Dizer
            quanto ficou de fora evita a leitura de que a diferença é erro de conta.
        -->
        <Aviso v-if="resumo.naoRealizavel > 0" class="mb-4">
            <strong>{{ formatarMoeda(resumo.naoRealizavel) }}</strong> em lançamentos
            cancelados e reembolsos rejeitados estão fora do total. Eles continuam na quebra
            por status abaixo, marcados como fora do total.
        </Aviso>

        <!-- Indicadores -->
        <div class="mb-5 grid gap-3.5 sm:grid-cols-2">
            <CardIndicador
                rotulo="Total no período"
                :valor="formatarMoeda(resumo.total)"
                :detalhe="`${resumo.quantidade} lançamento${resumo.quantidade === 1 ? '' : 's'}, pagamentos e reembolsos`"
                icone="wallet"
            />
            <CardIndicador
                rotulo="Ticket médio"
                :valor="formatarMoeda(ticketMedio)"
                detalhe="por lançamento"
                icone="chart"
            />
        </div>

        <div class="grid items-start gap-4 lg:grid-cols-[2fr_1fr]">
            <!-- Por status -->
            <div class="overflow-hidden rounded-lg border border-ink-8 bg-white shadow-card">
                <div class="border-b border-ink-8 px-5 py-4">
                    <h2 class="text-[12px] font-semibold uppercase tracking-[0.06em] text-ink-55">
                        Por status
                    </h2>
                </div>

                <DataTable :value="porStatus" data-key="status" size="small">
                    <template #empty>
                        <p class="py-10 text-center text-[13px] text-ink-55">
                            Nenhum lançamento no período escolhido.
                        </p>
                    </template>

                    <Column field="status" header="Status">
                        <template #body="{ data }">
                            <div class="flex items-center gap-2">
                                <StatusBadge :status="data.status" :rotulo="data.rotulo" />
                                <span v-if="!data.realizavel" class="text-[11.5px] text-ink-55">
                                    fora do total
                                </span>
                            </div>
                        </template>
                    </Column>

                    <Column
                        field="quantidade"
                        header="Quantidade"
                        class="text-right"
                        header-class="!text-right"
                    >
                        <template #body="{ data }">
                            <span class="mono">{{ data.quantidade }}</span>
                        </template>
                    </Column>

                    <Column
                        field="total"
                        header="Total"
                        class="text-right"
                        header-class="!text-right"
                    >
                        <template #body="{ data }">
                            <span class="mono font-semibold">{{ formatarMoeda(data.total) }}</span>
                        </template>
                    </Column>
                </DataTable>
            </div>

            <!-- Por categoria -->
            <div class="rounded-lg border border-ink-8 bg-white p-5 shadow-card">
                <h2 class="mb-3.5 text-[12px] font-semibold uppercase tracking-[0.06em] text-ink-55">
                    Por categoria
                </h2>

                <p v-if="!porCategoria.length" class="py-6 text-center text-[12.75px] text-ink-55">
                    Nenhum lançamento categorizado no período.
                </p>

                <div v-for="linha in porCategoria" :key="linha.nome" class="mb-3 last:mb-0">
                    <div class="mb-1.5 flex items-baseline justify-between gap-3 text-[12.5px]">
                        <span class="truncate font-medium">{{ linha.nome }}</span>
                        <span class="mono shrink-0 font-semibold">
                            {{ formatarMoeda(linha.total) }}
                        </span>
                    </div>
                    <div class="h-1.5 overflow-hidden rounded-full bg-ink-8">
                        <div
                            class="h-full rounded-full bg-laranja-400"
                            :style="{ width: `${proporcao(linha.total)}%` }"
                        />
                    </div>
                </div>
            </div>
        </div>

        <ModalExportarRelatorio
            v-model:visivel="modalExportar"
            :filtros="filtros"
            :resumo-filtros="resumoFiltros"
            :quantidade="resumo.quantidade"
        />
    </AuthenticatedLayout>
</template>
