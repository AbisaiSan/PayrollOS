<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import CabecalhoPagina from '@/Components/CabecalhoPagina.vue';
import CardIndicador from '@/Components/CardIndicador.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import { useFormato } from '@/Composables/useFormato';

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

const { formatarMoeda, formatarData, diasAte } = useFormato();

const totalCategorias = () =>
    props.porCategoria.reduce((soma, linha) => soma + linha.total, 0);

const percentual = (valor: number) => {
    const total = totalCategorias();

    return total > 0 ? (valor / total) * 100 : 0;
};

const legendaVencimento = (data: string) => {
    const dias = diasAte(data);

    if (dias === null) return '';
    if (dias < 0) return `${Math.abs(dias)} d atrás`;
    if (dias === 0) return 'hoje';
    if (dias === 1) return 'amanhã';

    return `em ${dias} d`;
};
</script>

<template>
    <Head title="Dashboard" />

    <AuthenticatedLayout>
        <template #header>
            <CabecalhoPagina
                titulo="Dashboard"
                :descricao="`Visão do mês corrente`"
            />
        </template>

        <div class="space-y-6">
            <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                <CardIndicador
                    rotulo="A pagar no mês"
                    :valor="formatarMoeda(indicadores.aPagarNoMes)"
                    detalhe="Pendentes, agendados e atrasados"
                    icone="pi pi-calendar"
                />
                <CardIndicador
                    rotulo="Pago no mês"
                    :valor="formatarMoeda(indicadores.pagoNoMes)"
                    detalhe="Confirmado manualmente"
                    icone="pi pi-check-circle"
                />
                <CardIndicador
                    rotulo="Atrasados"
                    :valor="formatarMoeda(indicadores.atrasados.valor)"
                    :detalhe="`${indicadores.atrasados.quantidade} lançamento(s) vencido(s)`"
                    icone="pi pi-exclamation-triangle"
                    :alerta="indicadores.atrasados.quantidade > 0"
                />
                <CardIndicador
                    rotulo="Reembolsos pendentes"
                    :valor="formatarMoeda(indicadores.reembolsosPendentes.valor)"
                    :detalhe="`${indicadores.reembolsosPendentes.quantidade} solicitação(ões)`"
                    icone="pi pi-receipt"
                />
            </div>

            <div class="grid gap-6 lg:grid-cols-3">
                <!-- Proximos vencimentos ocupa mais espaco: e o que se olha primeiro -->
                <div class="rounded-xl border border-black/5 bg-white lg:col-span-2">
                    <div class="flex items-center justify-between border-b border-black/5 px-5 py-4">
                        <h2 class="text-sm font-semibold text-corebanx-preto">
                            Próximos vencimentos
                        </h2>
                        <Link
                            :href="route('pagamentos.index')"
                            class="text-sm font-medium text-corebanx-laranja hover:underline"
                        >
                            Ver todos
                        </Link>
                    </div>

                    <DataTable
                        :value="proximosVencimentos"
                        size="small"
                        data-key="id"
                        :pt="{ table: { class: 'text-sm' } }"
                    >
                        <template #empty>
                            <p class="py-8 text-center text-sm text-corebanx-preto/45">
                                Nenhum vencimento nos próximos 7 dias.
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
                                    {{ data.beneficiario }}
                                </p>
                            </template>
                        </Column>

                        <Column field="data_vencimento" header="Vencimento">
                            <template #body="{ data }">
                                <span class="tabular-nums">
                                    {{ formatarData(data.data_vencimento) }}
                                </span>
                                <p class="text-xs text-corebanx-preto/50">
                                    {{ legendaVencimento(data.data_vencimento) }}
                                </p>
                            </template>
                        </Column>

                        <Column field="valor" header="Valor">
                            <template #body="{ data }">
                                <span class="font-medium tabular-nums">
                                    {{ formatarMoeda(data.valor) }}
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

                <div class="rounded-xl border border-black/5 bg-white">
                    <div class="border-b border-black/5 px-5 py-4">
                        <h2 class="text-sm font-semibold text-corebanx-preto">
                            Em aberto por categoria
                        </h2>
                    </div>

                    <div class="space-y-4 p-5">
                        <p
                            v-if="!porCategoria.length"
                            class="py-4 text-center text-sm text-corebanx-preto/45"
                        >
                            Nada em aberto neste mês.
                        </p>

                        <div v-for="linha in porCategoria" :key="linha.nome">
                            <div class="flex items-baseline justify-between gap-3 text-sm">
                                <span class="truncate text-corebanx-preto/70">
                                    {{ linha.nome }}
                                </span>
                                <span class="shrink-0 font-medium tabular-nums text-corebanx-preto">
                                    {{ formatarMoeda(linha.total) }}
                                </span>
                            </div>
                            <div class="mt-1.5 h-1.5 overflow-hidden rounded-full bg-corebanx-cinza">
                                <div
                                    class="h-full rounded-full bg-corebanx-laranja"
                                    :style="{ width: `${percentual(linha.total)}%` }"
                                />
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
