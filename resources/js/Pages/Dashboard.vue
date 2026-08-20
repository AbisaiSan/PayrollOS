<script setup lang="ts">
import { computed } from 'vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Button from 'primevue/button';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import CabecalhoPagina from '@/Components/CabecalhoPagina.vue';
import CardIndicador from '@/Components/CardIndicador.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import Icone from '@/Components/Icone.vue';
import { useFormato } from '@/Composables/useFormato';
import { usePermissoes } from '@/Composables/usePermissoes';
import type { PageProps } from '@/types';

interface ProximoVencimento {
    id: number;
    descricao: string;
    beneficiario: string;
    categoria: string | null;
    valor: string;
    data_vencimento: string;
    status: string;
}

const props = defineProps<{
    indicadores: {
        aPagarNoMes: number;
        pagoNoMes: number;
        atrasados: { quantidade: number; valor: number };
        reembolsosPendentes: { quantidade: number; valor: number };
    };
    porCategoria: Array<{ nome: string; total: number }>;
    proximosVencimentos: ProximoVencimento[];
}>();

const page = usePage<PageProps>();
const { formatarMoeda, formatarData, vencimentoRelativo } = useFormato();
const { pode } = usePermissoes();

const primeiroNome = computed(() => page.props.auth.user.name.split(' ')[0]);

const mesCorrente = computed(() => {
    const texto = new Intl.DateTimeFormat('pt-BR', {
        month: 'long',
        year: 'numeric',
    }).format(new Date());

    return texto.charAt(0).toUpperCase() + texto.slice(1);
});

/**
 * A barra é proporcional à maior categoria, não ao total.
 *
 * Com o total, uma categoria dominante — folha, quase sempre — esmaga todas as
 * outras em barras de poucos pixels, e a comparação entre elas se perde.
 */
const maiorCategoria = computed(() =>
    props.porCategoria.reduce((maior, linha) => Math.max(maior, linha.total), 0),
);

const proporcao = (valor: number) =>
    maiorCategoria.value > 0 ? Math.round((valor / maiorCategoria.value) * 100) : 0;

const abrirPagamento = (evento: { data: ProximoVencimento }) => {
    router.get(route('pagamentos.show', evento.data.id));
};
</script>

<template>
    <Head title="Dashboard" />

    <AuthenticatedLayout>
        <template #header>
            <CabecalhoPagina titulo="Dashboard" descricao="Panorama de pagamentos do mês corrente">
                <template #acoes>
                    <Link v-if="pode('relatorios.ver')" :href="route('relatorios.index')">
                        <Button label="Ver relatórios" severity="secondary" outlined size="small">
                            <template #icon><Icone nome="chart" :tamanho="16" /></template>
                        </Button>
                    </Link>
                </template>
            </CabecalhoPagina>
        </template>

        <p
            class="mb-1.5 text-[11px] font-bold uppercase tracking-[0.07em] text-laranja-600"
        >
            Visão do mês corrente · {{ mesCorrente }}
        </p>
        <h2 class="mb-0.5 text-[20px] font-bold -tracking-[0.01em]">Olá, {{ primeiroNome }}</h2>
        <p class="mb-5 max-w-[640px] text-[13px] text-ink-55">
            Panorama de pagamentos, atrasos e reembolsos. Atualizado às 06:15, após a rotina
            diária de vencimentos.
        </p>

        <!-- Indicadores -->
        <div class="mb-5 grid gap-3.5 sm:grid-cols-2 xl:grid-cols-4">
            <CardIndicador
                rotulo="A pagar no mês"
                :valor="formatarMoeda(indicadores.aPagarNoMes)"
                detalhe="Pendentes, agendados e atrasados"
                icone="wallet"
            />
            <CardIndicador
                rotulo="Pago no mês"
                :valor="formatarMoeda(indicadores.pagoNoMes)"
                detalhe="Confirmado manualmente"
                icone="checkCircle"
            />
            <CardIndicador
                rotulo="Atrasados"
                :valor="formatarMoeda(indicadores.atrasados.valor)"
                :detalhe="`${indicadores.atrasados.quantidade} lançamento(s) vencido(s)`"
                icone="alertTriangle"
                :alerta="indicadores.atrasados.quantidade > 0"
            />
            <CardIndicador
                rotulo="Reembolsos pendentes"
                :valor="formatarMoeda(indicadores.reembolsosPendentes.valor)"
                :detalhe="`${indicadores.reembolsosPendentes.quantidade} solicitação(ões)`"
                icone="receipt"
            />
        </div>

        <div class="grid items-start gap-4 lg:grid-cols-[2fr_1fr]">
            <!-- Próximos vencimentos -->
            <div class="overflow-hidden rounded-lg border border-ink-8 bg-white shadow-card">
                <div
                    class="flex items-baseline justify-between border-b border-ink-8 px-5 py-4"
                >
                    <h2 class="text-[14.5px] font-semibold">Próximos vencimentos</h2>
                    <Link
                        :href="route('pagamentos.index')"
                        class="text-[12px] font-semibold text-laranja-600 hover:underline"
                    >
                        Próximos 7 dias
                    </Link>
                </div>

                <DataTable
                    :value="proximosVencimentos"
                    data-key="id"
                    size="small"
                    :row-class="() => 'cursor-pointer'"
                    @row-click="abrirPagamento"
                >
                    <template #empty>
                        <p class="py-10 text-center text-[13px] text-ink-55">
                            Nenhum vencimento nos próximos 7 dias.
                        </p>
                    </template>

                    <Column field="descricao" header="Descrição">
                        <template #body="{ data }">
                            <div class="font-semibold text-ink">{{ data.descricao }}</div>
                            <div class="mt-0.5 text-[12px] text-ink-55">{{ data.beneficiario }}</div>
                        </template>
                    </Column>

                    <Column field="data_vencimento" header="Vencimento">
                        <template #body="{ data }">
                            <span class="mono">{{ formatarData(data.data_vencimento) }}</span>
                            <div class="mt-0.5 text-[12px] text-ink-55">
                                {{ vencimentoRelativo(data.data_vencimento) }}
                            </div>
                        </template>
                    </Column>

                    <Column
                        field="valor"
                        header="Valor"
                        class="text-right"
                        header-class="!text-right"
                    >
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

            <!-- Em aberto por categoria -->
            <div class="rounded-lg border border-ink-8 bg-white p-5 shadow-card">
                <h2 class="mb-3.5 text-[14.5px] font-semibold">Em aberto por categoria</h2>

                <p
                    v-if="!porCategoria.length"
                    class="py-6 text-center text-[12.75px] text-ink-55"
                >
                    Nada em aberto neste mês.
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
    </AuthenticatedLayout>
</template>
