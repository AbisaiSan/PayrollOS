<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Select from 'primevue/select';
import DatePicker from 'primevue/datepicker';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import CabecalhoPagina from '@/Components/CabecalhoPagina.vue';
import Icone from '@/Components/Icone.vue';
import { useFormato } from '@/Composables/useFormato';
import type { Opcao, Paginado } from '@/types';

interface Atividade {
    id: number;
    log: string;
    descricao: string;
    registro_tipo: string;
    registro_id: number | null;
    usuario: string | null;
    alteracoes: { attributes?: Record<string, unknown>; old?: Record<string, unknown> } | null;
    created_at: string | null;
}

const props = defineProps<{
    atividades: Paginado<Atividade>;
    filtros: Record<string, string | undefined>;
    opcoes: { logs: Opcao[]; usuarios: Array<{ id: number; name: string }> };
}>();

const { formatarDataHora, formatarData, formatarMoeda, formatarDocumento } = useFormato();

const log = ref(props.filtros.log ?? null);
const usuarioId = ref(props.filtros.usuario_id ?? null);
const inicio = ref<Date | null>(null);
const fim = ref<Date | null>(null);

const { paraDate, paraIso } = useFormato();
inicio.value = paraDate(props.filtros.inicio);
fim.value = paraDate(props.filtros.fim);

const temFiltro = computed(
    () => !!(log.value || usuarioId.value || inicio.value || fim.value),
);

const consultar = (extras: Record<string, unknown> = {}) => {
    router.get(
        route('auditoria.index'),
        {
            log: log.value || undefined,
            usuario_id: usuarioId.value || undefined,
            inicio: paraIso(inicio.value) ?? undefined,
            fim: paraIso(fim.value) ?? undefined,
            ...extras,
        },
        { preserveState: true, preserveScroll: true, replace: true },
    );
};

watch([log, usuarioId, inicio, fim], () => consultar());

const limparFiltros = () => {
    log.value = null;
    usuarioId.value = null;
    inicio.value = null;
    fim.value = null;
};

const mudarPagina = (evento: { page: number; rows: number }) => {
    consultar({ page: evento.page + 1, por_pagina: evento.rows });
};

const ROTULO_MODULO = computed(() =>
    Object.fromEntries(props.opcoes.logs.map((opcao) => [opcao.value, opcao.label])),
);

const EVENTO: Record<string, string> = {
    created: 'Cadastrou',
    updated: 'Alterou',
    deleted: 'Removeu',
    restored: 'Restaurou',
};

/* ------------------------------------------------------------------
 * Diferenças de campo, legíveis
 * ---------------------------------------------------------------- */

/**
 * O activitylog guarda os campos crus. Sem tradução, a linha sairia como
 * "salario_base: 7200.00 → 7800.00", que é exatamente o JSON que o plano manda
 * não mostrar. Os rótulos cobrem os campos que os seis models registram.
 */
const ROTULO_CAMPO: Record<string, string> = {
    nome: 'Nome',
    cpf: 'CPF',
    cargo: 'Cargo',
    departamento: 'Departamento',
    tipo_contrato: 'Tipo de contrato',
    salario_base: 'Salário base',
    status: 'Status',
    data_desligamento: 'Data de desligamento',
    razao_social: 'Razão social',
    nome_fantasia: 'Nome fantasia',
    documento: 'Documento',
    tipo_pessoa: 'Tipo de pessoa',
    tipo_fornecedor: 'Tipo de fornecedor',
    banco: 'Banco',
    agencia: 'Agência',
    conta: 'Conta',
    digito: 'Dígito',
    chave_pix: 'Chave Pix',
    tipo_chave_pix: 'Tipo da chave Pix',
    principal: 'Conta principal',
    descricao: 'Descrição',
    tipo: 'Tipo',
    valor: 'Valor',
    periodicidade: 'Periodicidade',
    dia_vencimento: 'Dia de vencimento',
    data_fim: 'Data de término',
    data_vencimento: 'Vencimento',
    data_pagamento: 'Data de pagamento',
    forma_pagamento: 'Forma de pagamento',
    categoria: 'Categoria',
    categoria_id: 'Categoria',
    conta_bancaria_id: 'Conta de destino',
};

/** Valores de enum que o log grava crus e a tela precisa dizer em português. */
const ROTULO_VALOR: Record<string, string> = {
    pendente: 'Pendente',
    agendado: 'Agendado',
    pago: 'Pago',
    atrasado: 'Atrasado',
    cancelado: 'Cancelado',
    aprovado: 'Aprovado',
    rejeitado: 'Rejeitado',
    ativo: 'Ativo',
    inativo: 'Inativo',
    afastado: 'Afastado',
    desligado: 'Desligado',
    ativa: 'Ativa',
    inativa: 'Inativa',
    suspenso: 'Suspenso',
    encerrado: 'Encerrado',
    clt: 'CLT',
    pj: 'PJ',
    estagio: 'Estágio',
    autonomo: 'Autônomo',
    pontual: 'Pontual',
    recorrente: 'Recorrente',
    mensal: 'Mensal',
    quinzenal: 'Quinzenal',
    anual: 'Anual',
    pix: 'Pix',
    ted: 'TED',
    boleto: 'Boleto',
    dinheiro: 'Dinheiro',
    viagem: 'Viagem',
    alimentacao: 'Alimentação',
    material: 'Material',
    transporte: 'Transporte',
    outro: 'Outro',
    produto: 'Fornecedor de produto',
    servico: 'Prestador de serviço',
    ambos: 'Ambos',
    pf: 'Pessoa Física',
};

const CAMPO_DINHEIRO = ['valor', 'salario_base'];
const CAMPO_DATA = ['data_desligamento', 'data_fim', 'data_vencimento', 'data_pagamento'];
const CAMPO_DOCUMENTO = ['cpf', 'documento'];

const formatarValor = (campo: string, valor: unknown) => {
    if (valor === null || valor === undefined || valor === '') return '—';
    if (typeof valor === 'boolean') return valor ? 'Sim' : 'Não';
    if (CAMPO_DINHEIRO.includes(campo)) return formatarMoeda(valor as string);
    if (CAMPO_DATA.includes(campo)) return formatarData(valor as string);
    if (CAMPO_DOCUMENTO.includes(campo)) return formatarDocumento(valor as string);

    const texto = String(valor);

    return ROTULO_VALOR[texto] ?? texto;
};

const usaMono = (campo: string) =>
    CAMPO_DINHEIRO.includes(campo) || CAMPO_DATA.includes(campo) || CAMPO_DOCUMENTO.includes(campo);

/**
 * Um "created" traz só `attributes`, sem `old` — nesse caso não há de/para, e
 * mostrar "— → valor" para cada campo seria ruído. Só o que mudou vira linha.
 */
const diferencas = (atividade: Atividade) => {
    const novos = atividade.alteracoes?.attributes ?? {};
    const antigos = atividade.alteracoes?.old ?? null;

    if (!antigos) return [];

    return Object.keys(novos)
        .filter((campo) => novos[campo] !== antigos[campo])
        .map((campo) => ({
            campo,
            rotulo: ROTULO_CAMPO[campo] ?? campo,
            de: formatarValor(campo, antigos[campo]),
            para: formatarValor(campo, novos[campo]),
            mono: usaMono(campo),
        }));
};

const camposCriados = (atividade: Atividade) => {
    if (atividade.alteracoes?.old) return [];

    const novos = atividade.alteracoes?.attributes ?? {};

    return Object.keys(novos).map((campo) => ROTULO_CAMPO[campo] ?? campo);
};
</script>

<template>
    <Head title="Auditoria" />

    <AuthenticatedLayout>
        <template #header>
            <CabecalhoPagina
                titulo="Auditoria"
                descricao="Toda mudança de status e cadastro, com quem, quando e o quê"
            />
        </template>

        <!-- Filtros -->
        <div class="mb-4 flex flex-wrap items-center gap-2.5">
            <Select
                v-model="log"
                :options="opcoes.logs"
                option-label="label"
                option-value="value"
                placeholder="Módulo — todos"
                show-clear
                size="small"
                class="w-[200px]"
                aria-label="Filtrar por módulo"
            />

            <Select
                v-model="usuarioId"
                :options="opcoes.usuarios"
                option-label="name"
                option-value="id"
                placeholder="Usuário — todos"
                show-clear
                filter
                filter-placeholder="Buscar usuário…"
                size="small"
                class="w-[220px]"
                aria-label="Filtrar por usuário"
            />

            <DatePicker
                v-model="inicio"
                date-format="dd/mm/yy"
                placeholder="Período — de"
                show-button-bar
                show-icon
                icon-display="input"
                size="small"
                class="w-[164px]"
                aria-label="Período de"
            />

            <DatePicker
                v-model="fim"
                date-format="dd/mm/yy"
                placeholder="Período — até"
                :min-date="inicio ?? undefined"
                show-button-bar
                show-icon
                icon-display="input"
                size="small"
                class="w-[164px]"
                aria-label="Período até"
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

        <div class="overflow-hidden rounded-lg border border-ink-8 bg-white shadow-card">
            <DataTable
                :value="atividades.data"
                data-key="id"
                size="small"
                lazy
                paginator
                :rows="atividades.per_page"
                :total-records="atividades.total"
                :first="(atividades.current_page - 1) * atividades.per_page"
                :rows-per-page-options="[30, 60, 100]"
                paginator-template="CurrentPageReport PrevPageLink PageLinks NextPageLink RowsPerPageDropdown"
                current-page-report-template="Mostrando {first}–{last} de {totalRecords}"
                @page="mudarPagina"
            >
                <template #empty>
                    <p class="py-12 text-center text-[13px] text-ink-55">
                        {{
                            temFiltro
                                ? 'Nenhuma alteração registrada com esses filtros.'
                                : 'Nenhuma alteração registrada ainda.'
                        }}
                    </p>
                </template>

                <Column field="created_at" header="Quando">
                    <template #body="{ data }">
                        <span class="mono whitespace-nowrap">
                            {{ formatarDataHora(data.created_at) }}
                        </span>
                    </template>
                </Column>

                <!--
                    Sem usuário a mudança veio das rotinas agendadas (06:00 gera os
                    recorrentes, 06:15 promove os vencidos), porque não há ninguém
                    autenticado no cron. Mesma leitura da linha do tempo.
                -->
                <Column field="usuario" header="Usuário">
                    <template #body="{ data }">
                        <span
                            v-if="!data.usuario"
                            class="rounded-full bg-azul-50 px-[7px] py-px text-[11px] font-semibold text-azul-600"
                        >
                            Rotina do sistema
                        </span>
                        <span v-else>{{ data.usuario }}</span>
                    </template>
                </Column>

                <Column field="log" header="Módulo">
                    <template #body="{ data }">
                        {{ ROTULO_MODULO[data.log] ?? data.log }}
                        <div v-if="data.registro_id" class="mono mt-0.5 text-[12px] text-ink-55">
                            #{{ data.registro_id }}
                        </div>
                    </template>
                </Column>

                <Column field="descricao" header="Evento">
                    <template #body="{ data }">
                        {{ EVENTO[data.descricao] ?? data.descricao }}
                    </template>
                </Column>

                <Column field="alteracoes" header="Alteração">
                    <template #body="{ data }">
                        <div
                            v-for="diferenca in diferencas(data)"
                            :key="diferenca.campo"
                            class="mb-1 flex flex-wrap items-baseline gap-1.5 text-[12.25px] last:mb-0"
                        >
                            <span class="text-ink-55">{{ diferenca.rotulo }}:</span>
                            <span
                                class="text-ink-70 line-through decoration-ink-35"
                                :class="diferenca.mono ? 'mono' : ''"
                            >
                                {{ diferenca.de }}
                            </span>
                            <Icone nome="chevronRight" :tamanho="11" class="text-ink-35" />
                            <span
                                class="font-semibold"
                                :class="diferenca.mono ? 'mono' : ''"
                            >
                                {{ diferenca.para }}
                            </span>
                        </div>

                        <span
                            v-if="camposCriados(data).length"
                            class="text-[12.25px] text-ink-55"
                        >
                            Registro criado com {{ camposCriados(data).join(', ') }}
                        </span>

                        <span
                            v-else-if="!diferencas(data).length"
                            class="text-[12.25px] text-ink-35"
                        >
                            —
                        </span>
                    </template>
                </Column>
            </DataTable>
        </div>
    </AuthenticatedLayout>
</template>
