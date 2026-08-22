<script setup lang="ts">
import { computed, ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Button from 'primevue/button';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import CabecalhoPagina from '@/Components/CabecalhoPagina.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import ContasBancarias from '@/Components/ContasBancarias.vue';
import ModalDesligarColaborador from '@/Components/ModalDesligarColaborador.vue';
import Aviso from '@/Components/Aviso.vue';
import Icone from '@/Components/Icone.vue';
import { useFormato } from '@/Composables/useFormato';
import { usePermissoes } from '@/Composables/usePermissoes';
import type { Colaborador, Opcao } from '@/types';

interface LinhaPagamento {
    id: number;
    descricao: string;
    valor: string;
    data_vencimento: string;
    status: string;
    categoria?: { nome: string } | null;
}

const props = defineProps<{
    colaborador: Colaborador & { usuario?: { id: number; name: string; email: string } | null };
    pagamentos: LinhaPagamento[];
    opcoes: { tipoConta: Opcao[]; tipoChavePix: Opcao[] };
}>();

const { formatarMoeda, formatarData, formatarDocumento } = useFormato();
const { pode } = usePermissoes();

const TIPO_CONTRATO: Record<string, string> = {
    clt: 'CLT',
    pj: 'PJ',
    estagio: 'Estágio',
    autonomo: 'Autônomo',
};

const campos = computed(() => [
    { rotulo: 'CPF', valor: formatarDocumento(props.colaborador.cpf), mono: true },
    { rotulo: 'Cargo', valor: props.colaborador.cargo },
    { rotulo: 'Departamento', valor: props.colaborador.departamento },
    {
        rotulo: 'Tipo de contrato',
        valor:
            TIPO_CONTRATO[props.colaborador.tipo_contrato] ?? props.colaborador.tipo_contrato,
    },
    { rotulo: 'Admissão', valor: formatarData(props.colaborador.data_admissao), mono: true },
    {
        rotulo: 'Salário base',
        valor: formatarMoeda(props.colaborador.salario_base),
        mono: true,
        destaque: true,
    },
    { rotulo: 'E-mail', valor: props.colaborador.email ?? '—' },
    { rotulo: 'Telefone', valor: props.colaborador.telefone ?? '—' },
]);

const estaDesligado = computed(() => props.colaborador.status === 'desligado');

const podeVerPagamentos = computed(() => pode('pagamentos.ver'));

const abrirPagamento = (evento: { data: LinhaPagamento }) => {
    if (!podeVerPagamentos.value) return;

    router.get(route('pagamentos.show', evento.data.id));
};

const classeLinha = () => (podeVerPagamentos.value ? 'cursor-pointer' : '');

const modalDesligar = ref(false);
</script>

<template>
    <Head :title="colaborador.nome" />

    <AuthenticatedLayout>
        <template #header>
            <CabecalhoPagina titulo="Colaborador" :descricao="colaborador.nome" />
        </template>

        <Link
            :href="route('colaboradores.index')"
            class="mb-3 inline-flex items-center gap-1.5 rounded-lg px-2.5 py-1.5 text-[12.25px] font-semibold text-ink-70 hover:bg-ink-8"
        >
            <Icone nome="chevronLeft" :tamanho="15" />
            Voltar para colaboradores
        </Link>

        <!--
            Faixa no topo, fora das colunas: o desligamento muda o que se pode fazer
            com este cadastro, então precisa ser lido antes de qualquer bloco.
        -->
        <Aviso v-if="colaborador.data_desligamento" class="mb-4">
            Desligado em {{ formatarData(colaborador.data_desligamento) }}. O histórico foi
            preservado e novos lançamentos de folha estão bloqueados.
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
                                {{ colaborador.nome }}
                            </h2>
                            <span class="text-[12px] text-ink-55">{{ colaborador.cargo }}</span>
                        </div>
                        <StatusBadge :status="colaborador.status" />
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
                                    class="m-0 font-medium"
                                    :class="[
                                        campo.mono ? 'mono' : '',
                                        campo.destaque ? 'text-[15.5px]' : 'text-[13.75px]',
                                    ]"
                                >
                                    {{ campo.valor }}
                                </dd>
                            </div>
                        </dl>

                        <p v-if="colaborador.usuario" class="mt-5 text-[11.75px] text-ink-55">
                            Acessa o sistema como
                            <strong class="font-semibold">{{ colaborador.usuario.name }}</strong>
                            ({{ colaborador.usuario.email }})
                        </p>
                    </div>

                    <!-- Ações -->
                    <div
                        v-if="pode('colaboradores.gerenciar')"
                        class="flex flex-wrap items-center gap-2.5 border-t border-ink-8 px-5 py-4"
                    >
                        <Link :href="route('colaboradores.edit', colaborador.id)">
                            <Button label="Editar cadastro" severity="secondary" outlined size="small">
                                <template #icon><Icone nome="edit" :tamanho="16" /></template>
                            </Button>
                        </Link>

                        <Button
                            v-if="!estaDesligado"
                            label="Desligar"
                            severity="danger"
                            text
                            size="small"
                            class="ml-auto"
                            @click="modalDesligar = true"
                        >
                            <template #icon><Icone nome="slashCircle" :tamanho="16" /></template>
                        </Button>
                    </div>
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
                        :row-class="classeLinha"
                        @row-click="abrirPagamento"
                    >
                        <template #empty>
                            <p class="py-10 text-center text-[13px] text-ink-55">
                                Nenhum pagamento lançado para este colaborador.
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

            <!-- Painel de contas: o visual próprio dele é a tarefa 17 -->
            <ContasBancarias
                tipo-beneficiario="colaborador"
                :beneficiario-id="colaborador.id"
                :contas="colaborador.contas_bancarias ?? []"
                :opcoes="opcoes"
            />
        </div>

        <ModalDesligarColaborador
            v-if="!estaDesligado"
            v-model:visivel="modalDesligar"
            :colaborador="colaborador"
        />
    </AuthenticatedLayout>
</template>
