<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import InputText from 'primevue/inputtext';
import InputNumber from 'primevue/inputnumber';
import Select from 'primevue/select';
import DatePicker from 'primevue/datepicker';
import Textarea from 'primevue/textarea';
import Button from 'primevue/button';
import Message from 'primevue/message';
import AutoComplete from 'primevue/autocomplete';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import CabecalhoPagina from '@/Components/CabecalhoPagina.vue';
import Aviso from '@/Components/Aviso.vue';
import Icone from '@/Components/Icone.vue';
import { useFormato } from '@/Composables/useFormato';
import type { Opcao } from '@/types';

interface Beneficiario {
    id: number;
    tipo: 'colaborador' | 'fornecedor';
    nome: string;
    documento: string;
    detalhe: string;
}

interface ContaOpcao {
    id: number;
    resumo: string;
    principal: boolean;
}

interface ContratoOpcao {
    id: number;
    descricao: string;
    valor: string;
    categoria_id: number | null;
}

const props = defineProps<{
    pagamento: (Record<string, unknown> & { id: number; beneficiario_nome?: string }) | null;
    opcoes: {
        categorias: Array<{ id: number; nome: string; tipo: string }>;
        formaPagamento: Opcao[];
        status: Opcao[];
    };
}>();

const { paraDate, paraIso, formatarMoeda } = useFormato();

const editando = props.pagamento !== null;

const TIPOS = [
    { valor: 'colaborador' as const, rotulo: 'Colaborador', icone: 'users' },
    { valor: 'fornecedor' as const, rotulo: 'Fornecedor', icone: 'briefcase' },
];

const form = useForm({
    payable_type: (props.pagamento?.payable_type as 'colaborador' | 'fornecedor') ?? 'colaborador',
    payable_id: (props.pagamento?.payable_id as number | null) ?? null,
    categoria_id: (props.pagamento?.categoria_id as number | null) ?? null,
    contrato_id: (props.pagamento?.contrato_id as number | null) ?? null,
    conta_bancaria_id: (props.pagamento?.conta_bancaria_id as number | null) ?? null,
    competencia: paraDate((props.pagamento?.competencia as string | null)?.concat('-01') ?? null),
    descricao: (props.pagamento?.descricao as string) ?? '',
    valor: props.pagamento ? Number(props.pagamento.valor) : null,
    data_vencimento: paraDate(props.pagamento?.data_vencimento as string | null),
    forma_pagamento: (props.pagamento?.forma_pagamento as string) ?? 'pix',
    status: (props.pagamento?.status as string) ?? 'pendente',
    observacoes: (props.pagamento?.observacoes as string) ?? '',
});

/* ------------------------------------------------------------------
 * Encadeamento 1: o tipo filtra a busca de beneficiário
 * ---------------------------------------------------------------- */
const beneficiario = ref<Beneficiario | null>(
    editando && props.pagamento?.beneficiario_nome
        ? {
              id: form.payable_id as number,
              tipo: form.payable_type,
              nome: props.pagamento.beneficiario_nome,
              documento: '',
              detalhe: '',
          }
        : null,
);

const sugestoes = ref<Beneficiario[]>([]);
const buscando = ref(false);

const buscarBeneficiarios = async (evento: { query: string }) => {
    buscando.value = true;

    try {
        const url = route('beneficiarios.buscar', {
            tipo: form.payable_type,
            termo: evento.query,
        });
        const resposta = await fetch(url, { headers: { Accept: 'application/json' } });
        const corpo = await resposta.json();
        sugestoes.value = corpo.dados ?? [];
    } finally {
        buscando.value = false;
    }
};

const trocarTipo = (tipo: 'colaborador' | 'fornecedor') => {
    if (form.payable_type === tipo) return;

    form.payable_type = tipo;
    // O beneficiário anterior é de outro cadastro: limpar ele e tudo que dependia dele.
    beneficiario.value = null;
    form.payable_id = null;
    limparVinculos();
};

/* ------------------------------------------------------------------
 * Encadeamento 2: o beneficiário filtra as contas de destino
 * ---------------------------------------------------------------- */
const contas = ref<ContaOpcao[]>([]);
const contratos = ref<ContratoOpcao[]>([]);
const carregandoVinculos = ref(false);

const limparVinculos = () => {
    contas.value = [];
    contratos.value = [];
    form.conta_bancaria_id = null;
    form.contrato_id = null;
};

const carregarVinculos = async (tipo: string, id: number) => {
    carregandoVinculos.value = true;

    try {
        const resposta = await fetch(route('beneficiarios.dados', { tipo, id }), {
            headers: { Accept: 'application/json' },
        });
        const corpo = await resposta.json();

        contas.value = corpo.contas ?? [];
        contratos.value = corpo.contratos ?? [];

        // Conta principal já vem selecionada: é o destino padrão do beneficiário.
        const principal = contas.value.find((c) => c.principal);
        if (principal && !form.conta_bancaria_id) {
            form.conta_bancaria_id = principal.id;
        }
    } finally {
        carregandoVinculos.value = false;
    }
};

watch(beneficiario, (novo) => {
    if (!novo) {
        form.payable_id = null;
        limparVinculos();

        return;
    }

    form.payable_id = novo.id;
    limparVinculos();
    carregarVinculos(novo.tipo, novo.id);
});

// Ao editar, as contas do beneficiário já precisam estar carregadas na abertura.
if (editando && form.payable_id) {
    carregarVinculos(form.payable_type, form.payable_id);
}

/* ------------------------------------------------------------------
 * Encadeamento 3: Pix e TED exigem conta de destino
 * ---------------------------------------------------------------- */
const contaObrigatoria = computed(() => ['pix', 'ted'].includes(form.forma_pagamento));

const semContaCadastrada = computed(
    () => !!beneficiario.value && !carregandoVinculos.value && contas.value.length === 0,
);

/** Preenche valor e categoria a partir do contrato escolhido, poupando digitação. */
const aplicarContrato = (contratoId: number | null) => {
    const contrato = contratos.value.find((c) => c.id === contratoId);
    if (!contrato) return;

    form.valor = Number(contrato.valor);

    if (contrato.categoria_id) {
        form.categoria_id = contrato.categoria_id;
    }

    if (!form.descricao) {
        form.descricao = contrato.descricao;
    }
};

const enviar = () => {
    const dados = form.transform((campos) => ({
        ...campos,
        data_vencimento: paraIso(campos.data_vencimento),
        // O backend espera AAAA-MM; o seletor devolve o primeiro dia do mês.
        competencia: campos.competencia
            ? (paraIso(campos.competencia) as string).slice(0, 7)
            : null,
    }));

    if (editando && props.pagamento) {
        dados.put(route('pagamentos.update', props.pagamento.id));

        return;
    }

    dados.post(route('pagamentos.store'));
};
</script>

<template>
    <Head :title="editando ? 'Editar lançamento' : 'Lançar pagamento'" />

    <AuthenticatedLayout>
        <template #header>
            <CabecalhoPagina
                :titulo="editando ? 'Editar lançamento' : 'Lançar pagamento'"
                descricao="Registro de folha, fornecedor, prestador ou reembolso"
            />
        </template>

        <div class="max-w-[960px]">
            <Link
                :href="route('pagamentos.index')"
                class="mb-3 inline-flex items-center gap-1.5 rounded-lg px-2.5 py-1.5 text-[12.25px] font-semibold text-ink-70 hover:bg-ink-8"
            >
                <Icone nome="chevronLeft" :tamanho="15" />
                Voltar para pagamentos
            </Link>

            <Aviso class="mb-4">
                <strong>Este formulário não move dinheiro.</strong> Ele registra a intenção de
                pagamento. A confirmação, feita depois pelo internet banking, é uma etapa
                separada.
            </Aviso>

            <form class="space-y-4" @submit.prevent="enviar">
                <!-- Beneficiário -->
                <div class="rounded-lg border border-ink-8 bg-white p-5 shadow-card">
                    <h3 class="mb-1 text-[14px] font-semibold">Beneficiário</h3>
                    <p class="mb-4 text-[12px] text-ink-55">
                        O tipo escolhido filtra a busca abaixo — e a conta de destino, mais
                        adiante, mostra só as contas ativas dele.
                    </p>

                    <div class="grid gap-4">
                        <div class="flex flex-col gap-1.5">
                            <label class="text-[12.75px] font-semibold text-ink-90">
                                Tipo de beneficiário <span class="text-laranja-600">●</span>
                            </label>
                            <div class="flex gap-0.5 rounded-lg bg-ink-8 p-[3px]">
                                <button
                                    v-for="tipo in TIPOS"
                                    :key="tipo.valor"
                                    type="button"
                                    class="flex flex-1 items-center justify-center gap-1.5 rounded-md py-2 text-[13px] font-semibold transition-colors"
                                    :class="
                                        form.payable_type === tipo.valor
                                            ? 'bg-white text-ink shadow-card'
                                            : 'text-ink-55 hover:text-ink-70'
                                    "
                                    @click="trocarTipo(tipo.valor)"
                                >
                                    <Icone :nome="tipo.icone" :tamanho="15" />
                                    {{ tipo.rotulo }}
                                </button>
                            </div>
                        </div>

                        <div class="flex flex-col gap-1.5">
                            <label for="beneficiario" class="text-[12.75px] font-semibold text-ink-90">
                                Beneficiário <span class="text-laranja-600">●</span>
                            </label>
                            <AutoComplete
                                id="beneficiario"
                                v-model="beneficiario"
                                :suggestions="sugestoes"
                                option-label="nome"
                                :loading="buscando"
                                force-selection
                                dropdown
                                class="w-full"
                                input-class="w-full"
                                :placeholder="`Buscar ${form.payable_type} ativo…`"
                                :invalid="!!form.errors.payable_id"
                                @complete="buscarBeneficiarios"
                            >
                                <template #option="{ option }">
                                    <div class="flex flex-col">
                                        <span class="text-[13px] font-medium">{{ option.nome }}</span>
                                        <span class="mono text-[11.5px] text-ink-55">
                                            {{ option.documento }} · {{ option.detalhe }}
                                        </span>
                                    </div>
                                </template>
                            </AutoComplete>
                            <span class="text-[11.5px] text-azul-600">
                                Filtrado por:
                                {{ form.payable_type === 'colaborador' ? 'Colaborador' : 'Fornecedor' }}
                                · somente ativos
                            </span>
                            <Message
                                v-if="form.errors.payable_id"
                                severity="error"
                                size="small"
                                variant="simple"
                            >
                                {{ form.errors.payable_id }}
                            </Message>
                        </div>
                    </div>
                </div>

                <!-- Dados do lançamento -->
                <div class="rounded-lg border border-ink-8 bg-white p-5 shadow-card">
                    <h3 class="mb-4 text-[14px] font-semibold">Dados do lançamento</h3>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div class="flex flex-col gap-1.5">
                            <label for="categoria" class="text-[12.75px] font-semibold text-ink-90">
                                Categoria <span class="text-laranja-600">●</span>
                            </label>
                            <Select
                                id="categoria"
                                v-model="form.categoria_id"
                                :options="opcoes.categorias"
                                option-label="nome"
                                option-value="id"
                                placeholder="Selecione"
                                class="w-full"
                                :invalid="!!form.errors.categoria_id"
                            />
                            <Message
                                v-if="form.errors.categoria_id"
                                severity="error"
                                size="small"
                                variant="simple"
                            >
                                {{ form.errors.categoria_id }}
                            </Message>
                        </div>

                        <div class="flex flex-col gap-1.5">
                            <label for="contrato" class="text-[12.75px] font-semibold text-ink-90">
                                Contrato
                            </label>
                            <Select
                                id="contrato"
                                v-model="form.contrato_id"
                                :options="contratos"
                                option-label="descricao"
                                option-value="id"
                                placeholder="Nenhum — lançamento avulso"
                                show-clear
                                :disabled="!contratos.length"
                                class="w-full"
                                @change="aplicarContrato(form.contrato_id)"
                            />
                            <span class="text-[11.5px] text-ink-55">
                                {{
                                    form.payable_type === 'colaborador'
                                        ? 'Contratos existem apenas para fornecedores'
                                        : 'Escolher um contrato preenche valor e categoria'
                                }}
                            </span>
                        </div>

                        <div class="flex flex-col gap-1.5 sm:col-span-2">
                            <label for="descricao" class="text-[12.75px] font-semibold text-ink-90">
                                Descrição <span class="text-laranja-600">●</span>
                            </label>
                            <InputText
                                id="descricao"
                                v-model="form.descricao"
                                maxlength="255"
                                class="w-full"
                                :invalid="!!form.errors.descricao"
                            />
                            <Message
                                v-if="form.errors.descricao"
                                severity="error"
                                size="small"
                                variant="simple"
                            >
                                {{ form.errors.descricao }}
                            </Message>
                        </div>

                        <div class="flex flex-col gap-1.5">
                            <label for="valor" class="text-[12.75px] font-semibold text-ink-90">
                                Valor <span class="text-laranja-600">●</span>
                            </label>
                            <InputNumber
                                id="valor"
                                v-model="form.valor"
                                mode="currency"
                                currency="BRL"
                                locale="pt-BR"
                                :min="0.01"
                                class="w-full"
                                input-class="mono"
                                :invalid="!!form.errors.valor"
                            />
                            <Message
                                v-if="form.errors.valor"
                                severity="error"
                                size="small"
                                variant="simple"
                            >
                                {{ form.errors.valor }}
                            </Message>
                        </div>

                        <div class="flex flex-col gap-1.5">
                            <label for="vencimento" class="text-[12.75px] font-semibold text-ink-90">
                                Data de vencimento <span class="text-laranja-600">●</span>
                            </label>
                            <DatePicker
                                id="vencimento"
                                v-model="form.data_vencimento"
                                date-format="dd/mm/yy"
                                show-icon
                                icon-display="input"
                                class="w-full"
                                :invalid="!!form.errors.data_vencimento"
                            />
                            <Message
                                v-if="form.errors.data_vencimento"
                                severity="error"
                                size="small"
                                variant="simple"
                            >
                                {{ form.errors.data_vencimento }}
                            </Message>
                        </div>

                        <div class="flex flex-col gap-1.5">
                            <label for="competencia" class="text-[12.75px] font-semibold text-ink-90">
                                Competência
                            </label>
                            <DatePicker
                                id="competencia"
                                v-model="form.competencia"
                                view="month"
                                date-format="M/yy"
                                show-icon
                                icon-display="input"
                                class="w-full"
                                :invalid="!!form.errors.competencia"
                            />
                            <span class="text-[11.5px] text-ink-55">Mês de referência da folha</span>
                            <Message
                                v-if="form.errors.competencia"
                                severity="error"
                                size="small"
                                variant="simple"
                            >
                                {{ form.errors.competencia }}
                            </Message>
                        </div>

                        <div class="flex flex-col gap-1.5">
                            <label for="forma" class="text-[12.75px] font-semibold text-ink-90">
                                Forma de pagamento <span class="text-laranja-600">●</span>
                            </label>
                            <Select
                                id="forma"
                                v-model="form.forma_pagamento"
                                :options="opcoes.formaPagamento"
                                option-label="label"
                                option-value="value"
                                class="w-full"
                                :invalid="!!form.errors.forma_pagamento"
                            />
                        </div>

                        <div class="flex flex-col gap-1.5">
                            <label for="conta" class="text-[12.75px] font-semibold text-ink-90">
                                Conta de destino
                                <span v-if="contaObrigatoria" class="font-bold text-perigo">●</span>
                            </label>
                            <Select
                                id="conta"
                                v-model="form.conta_bancaria_id"
                                :options="contas"
                                option-label="resumo"
                                option-value="id"
                                :placeholder="
                                    beneficiario ? 'Selecione a conta' : 'Escolha o beneficiário antes'
                                "
                                :disabled="!beneficiario || carregandoVinculos || !contas.length"
                                :loading="carregandoVinculos"
                                show-clear
                                class="w-full"
                                :invalid="!!form.errors.conta_bancaria_id"
                            >
                                <template #option="{ option }">
                                    <div class="flex items-center gap-2">
                                        <span>{{ option.resumo }}</span>
                                        <span
                                            v-if="option.principal"
                                            class="rounded-full bg-laranja-50 px-2 py-px text-[11px] font-semibold text-laranja-700"
                                        >
                                            Principal
                                        </span>
                                    </div>
                                </template>
                            </Select>
                            <span v-if="contaObrigatoria" class="text-[11.5px] text-azul-600">
                                Obrigatório para Pix e TED · só contas ativas
                            </span>
                            <span v-else class="text-[11.5px] text-ink-55">
                                Dispensável em boleto e dinheiro
                            </span>
                            <Message
                                v-if="form.errors.conta_bancaria_id"
                                severity="error"
                                size="small"
                                variant="simple"
                            >
                                {{ form.errors.conta_bancaria_id }}
                            </Message>
                        </div>

                        <div class="flex flex-col gap-1.5">
                            <label for="status" class="text-[12.75px] font-semibold text-ink-90">
                                Status inicial
                            </label>
                            <Select
                                id="status"
                                v-model="form.status"
                                :options="opcoes.status"
                                option-label="label"
                                option-value="value"
                                class="w-full"
                            />
                            <span class="text-[11.5px] text-ink-55">
                                Nada nasce pago — só Pendente ou Agendado
                            </span>
                        </div>

                        <div class="flex flex-col gap-1.5 sm:col-span-2">
                            <label for="observacoes" class="text-[12.75px] font-semibold text-ink-90">
                                Observações
                            </label>
                            <Textarea
                                id="observacoes"
                                v-model="form.observacoes"
                                rows="3"
                                maxlength="5000"
                                class="w-full"
                            />
                        </div>
                    </div>

                    <Aviso v-if="semContaCadastrada" tom="atencao" icone="alertTriangle" class="mt-4">
                        Este beneficiário não tem conta ativa cadastrada.
                        {{
                            contaObrigatoria
                                ? 'Pix e TED exigem conta de destino — cadastre uma antes de lançar, ou escolha boleto ou dinheiro.'
                                : 'Boleto e dinheiro dispensam conta, então o lançamento pode seguir.'
                        }}
                    </Aviso>
                </div>

                <!-- Ações -->
                <div class="rounded-lg border border-ink-8 bg-white shadow-card">
                    <div class="flex justify-end gap-2.5 px-5 py-4">
                        <Link :href="route('pagamentos.index')">
                            <Button
                                label="Cancelar"
                                severity="secondary"
                                outlined
                                size="small"
                                :disabled="form.processing"
                            />
                        </Link>
                        <Button
                            type="submit"
                            :label="editando ? 'Salvar alterações' : 'Lançar pagamento'"
                            size="small"
                            :loading="form.processing"
                        >
                            <template #icon><Icone nome="checkCircle" :tamanho="16" /></template>
                        </Button>
                    </div>
                </div>
            </form>
        </div>
    </AuthenticatedLayout>
</template>
