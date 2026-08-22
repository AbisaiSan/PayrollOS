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
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import CabecalhoPagina from '@/Components/CabecalhoPagina.vue';
import Aviso from '@/Components/Aviso.vue';
import Icone from '@/Components/Icone.vue';
import { useFormato } from '@/Composables/useFormato';
import type { Opcao } from '@/types';

interface FornecedorOpcao {
    id: number;
    razao_social: string;
    nome_fantasia: string | null;
}

interface ContaOpcao {
    id: number;
    resumo: string;
    principal: boolean;
}

const props = defineProps<{
    contrato: (Record<string, unknown> & { id: number }) | null;
    opcoes: {
        fornecedores: FornecedorOpcao[];
        categorias: Array<{ id: number; nome: string }>;
        tipo: Opcao[];
        periodicidade: Opcao[];
        status: Opcao[];
    };
}>();

const { paraDate, paraIso } = useFormato();

const editando = props.contrato !== null;

const TIPOS = [
    { valor: 'pontual' as const, rotulo: 'Pontual', icone: 'file' },
    { valor: 'recorrente' as const, rotulo: 'Recorrente', icone: 'refresh' },
];

const form = useForm({
    fornecedor_id: (props.contrato?.fornecedor_id as number | null) ?? null,
    categoria_id: (props.contrato?.categoria_id as number | null) ?? null,
    conta_bancaria_id: (props.contrato?.conta_bancaria_id as number | null) ?? null,
    descricao: (props.contrato?.descricao as string) ?? '',
    tipo: (props.contrato?.tipo as 'pontual' | 'recorrente') ?? 'recorrente',
    valor: props.contrato ? Number(props.contrato.valor) : null,
    periodicidade: (props.contrato?.periodicidade as string | null) ?? null,
    dia_vencimento: (props.contrato?.dia_vencimento as number | null) ?? null,
    data_inicio: paraDate(props.contrato?.data_inicio as string | null),
    data_fim: paraDate(props.contrato?.data_fim as string | null),
    status: (props.contrato?.status as string) ?? 'ativo',
    observacoes: (props.contrato?.observacoes as string) ?? '',
});

const ehRecorrente = computed(() => form.tipo === 'recorrente');

/**
 * Só a periodicidade e o dia de vencimento dizem à rotina das 06:00 quando criar
 * o próximo lançamento. Num contrato pontual eles não têm sentido, e deixar
 * valores para trás faria o backend recusar por regra que a tela não mostra mais.
 */
const trocarTipo = (tipo: 'pontual' | 'recorrente') => {
    if (form.tipo === tipo) return;

    form.tipo = tipo;

    if (tipo === 'pontual') {
        form.periodicidade = null;
        form.dia_vencimento = null;
        form.clearErrors('periodicidade', 'dia_vencimento');
    }
};

/* ------------------------------------------------------------------
 * O fornecedor escolhido filtra as contas de destino
 * ---------------------------------------------------------------- */
const contas = ref<ContaOpcao[]>([]);
const carregandoContas = ref(false);

const carregarContas = async (fornecedorId: number) => {
    carregandoContas.value = true;

    try {
        const resposta = await fetch(
            route('beneficiarios.dados', { tipo: 'fornecedor', id: fornecedorId }),
            { headers: { Accept: 'application/json' } },
        );
        const corpo = await resposta.json();

        contas.value = corpo.contas ?? [];
    } finally {
        carregandoContas.value = false;
    }
};

watch(
    () => form.fornecedor_id,
    (novo) => {
        contas.value = [];
        form.conta_bancaria_id = null;

        if (novo) {
            carregarContas(novo);
        }
    },
);

if (editando && form.fornecedor_id) {
    carregarContas(form.fornecedor_id);
}

const contaPrincipal = computed(() => contas.value.find((conta) => conta.principal));

const enviar = () => {
    const dados = form.transform((campos) => ({
        ...campos,
        data_inicio: paraIso(campos.data_inicio),
        data_fim: paraIso(campos.data_fim),
    }));

    if (editando && props.contrato) {
        dados.put(route('contratos.update', props.contrato.id));

        return;
    }

    dados.post(route('contratos.store'));
};
</script>

<template>
    <Head :title="editando ? 'Editar contrato' : 'Novo contrato'" />

    <AuthenticatedLayout>
        <template #header>
            <CabecalhoPagina
                :titulo="editando ? 'Editar contrato' : 'Novo contrato'"
                descricao="Vínculo com fornecedor — pontual ou recorrente"
            />
        </template>

        <div class="max-w-[960px]">
            <Link
                :href="route('contratos.index')"
                class="mb-3 inline-flex items-center gap-1.5 rounded-lg px-2.5 py-1.5 text-[12.25px] font-semibold text-ink-70 hover:bg-ink-8"
            >
                <Icone nome="chevronLeft" :tamanho="15" />
                Voltar para contratos
            </Link>

            <form class="space-y-4" @submit.prevent="enviar">
                <div class="rounded-lg border border-ink-8 bg-white p-5 shadow-card">
                    <h3 class="mb-1 text-[14px] font-semibold">Dados do contrato</h3>
                    <p class="mb-4 text-[12px] text-ink-55">
                        Escolher "Recorrente" revela periodicidade e dia de vencimento — são eles
                        que alimentam a geração automática às 06:00.
                    </p>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div class="flex flex-col gap-1.5 sm:col-span-2">
                            <label for="fornecedor" class="text-[12.75px] font-semibold text-ink-90">
                                Fornecedor <span class="text-laranja-600">●</span>
                            </label>
                            <Select
                                id="fornecedor"
                                v-model="form.fornecedor_id"
                                :options="opcoes.fornecedores"
                                option-label="razao_social"
                                option-value="id"
                                placeholder="Selecione"
                                filter
                                filter-placeholder="Buscar fornecedor…"
                                class="w-full"
                                :invalid="!!form.errors.fornecedor_id"
                            >
                                <template #option="{ option }">
                                    <div class="flex flex-col">
                                        <span class="text-[13px] font-medium">
                                            {{ option.razao_social }}
                                        </span>
                                        <span
                                            v-if="option.nome_fantasia"
                                            class="text-[11.5px] text-ink-55"
                                        >
                                            {{ option.nome_fantasia }}
                                        </span>
                                    </div>
                                </template>
                            </Select>
                            <span class="text-[11.5px] text-ink-55">Somente fornecedores ativos</span>
                            <Message
                                v-if="form.errors.fornecedor_id"
                                severity="error"
                                size="small"
                                variant="simple"
                            >
                                {{ form.errors.fornecedor_id }}
                            </Message>
                        </div>

                        <div class="flex flex-col gap-1.5">
                            <label for="categoria" class="text-[12.75px] font-semibold text-ink-90">
                                Categoria
                            </label>
                            <Select
                                id="categoria"
                                v-model="form.categoria_id"
                                :options="opcoes.categorias"
                                option-label="nome"
                                option-value="id"
                                placeholder="Sem categoria"
                                show-clear
                                class="w-full"
                                :invalid="!!form.errors.categoria_id"
                            />
                            <span class="text-[11.5px] text-ink-55">
                                Vai para os lançamentos que este contrato gerar
                            </span>
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
                            <label for="conta" class="text-[12.75px] font-semibold text-ink-90">
                                Conta de destino
                            </label>
                            <Select
                                id="conta"
                                v-model="form.conta_bancaria_id"
                                :options="contas"
                                option-label="resumo"
                                option-value="id"
                                :placeholder="
                                    form.fornecedor_id
                                        ? 'Usar a principal do fornecedor'
                                        : 'Escolha o fornecedor antes'
                                "
                                :disabled="!form.fornecedor_id || carregandoContas || !contas.length"
                                :loading="carregandoContas"
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
                            <span v-if="contaPrincipal" class="text-[11.5px] text-ink-55">
                                Em branco, o lançamento vai para {{ contaPrincipal.resumo }}
                            </span>
                            <span v-else class="text-[11.5px] text-ink-55">
                                Em branco, usa a conta principal do fornecedor
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

                        <div class="flex flex-col gap-1.5 sm:col-span-2">
                            <label for="descricao" class="text-[12.75px] font-semibold text-ink-90">
                                Descrição <span class="text-laranja-600">●</span>
                            </label>
                            <InputText
                                id="descricao"
                                v-model="form.descricao"
                                maxlength="255"
                                placeholder="Ex.: Contabilidade, Aluguel"
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

                        <div class="flex flex-col gap-1.5 sm:col-span-2">
                            <label class="text-[12.75px] font-semibold text-ink-90">
                                Tipo <span class="text-laranja-600">●</span>
                            </label>
                            <div class="flex gap-0.5 rounded-lg bg-ink-8 p-[3px]">
                                <button
                                    v-for="opcao in TIPOS"
                                    :key="opcao.valor"
                                    type="button"
                                    class="flex flex-1 items-center justify-center gap-1.5 rounded-md py-2 text-[13px] font-semibold transition-colors"
                                    :class="
                                        form.tipo === opcao.valor
                                            ? 'bg-white text-ink shadow-card'
                                            : 'text-ink-55 hover:text-ink-70'
                                    "
                                    @click="trocarTipo(opcao.valor)"
                                >
                                    <Icone :nome="opcao.icone" :tamanho="15" />
                                    {{ opcao.rotulo }}
                                </button>
                            </div>
                            <Message
                                v-if="form.errors.tipo"
                                severity="error"
                                size="small"
                                variant="simple"
                            >
                                {{ form.errors.tipo }}
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
                            <label for="inicio" class="text-[12.75px] font-semibold text-ink-90">
                                Data de início <span class="text-laranja-600">●</span>
                            </label>
                            <DatePicker
                                id="inicio"
                                v-model="form.data_inicio"
                                date-format="dd/mm/yy"
                                show-icon
                                icon-display="input"
                                class="w-full"
                                :invalid="!!form.errors.data_inicio"
                            />
                            <Message
                                v-if="form.errors.data_inicio"
                                severity="error"
                                size="small"
                                variant="simple"
                            >
                                {{ form.errors.data_inicio }}
                            </Message>
                        </div>

                        <!-- Só aparecem no recorrente: são a entrada da rotina das 06:00 -->
                        <div v-if="ehRecorrente" class="flex flex-col gap-1.5">
                            <label
                                for="periodicidade"
                                class="text-[12.75px] font-semibold text-ink-90"
                            >
                                Periodicidade <span class="font-bold text-perigo">●</span>
                            </label>
                            <Select
                                id="periodicidade"
                                v-model="form.periodicidade"
                                :options="opcoes.periodicidade"
                                option-label="label"
                                option-value="value"
                                placeholder="Selecione"
                                class="w-full"
                                :invalid="!!form.errors.periodicidade"
                            />
                            <span class="text-[11.5px] text-azul-600">
                                Obrigatório — contrato recorrente
                            </span>
                            <Message
                                v-if="form.errors.periodicidade"
                                severity="error"
                                size="small"
                                variant="simple"
                            >
                                {{ form.errors.periodicidade }}
                            </Message>
                        </div>

                        <div v-if="ehRecorrente" class="flex flex-col gap-1.5">
                            <label for="dia" class="text-[12.75px] font-semibold text-ink-90">
                                Dia de vencimento <span class="font-bold text-perigo">●</span>
                            </label>
                            <InputNumber
                                id="dia"
                                v-model="form.dia_vencimento"
                                :min="1"
                                :max="31"
                                :use-grouping="false"
                                placeholder="1–31"
                                class="w-full"
                                input-class="mono"
                                :invalid="!!form.errors.dia_vencimento"
                            />
                            <span class="text-[11.5px] text-azul-600">
                                Obrigatório — contrato recorrente
                            </span>
                            <Message
                                v-if="form.errors.dia_vencimento"
                                severity="error"
                                size="small"
                                variant="simple"
                            >
                                {{ form.errors.dia_vencimento }}
                            </Message>
                        </div>

                        <div class="flex flex-col gap-1.5">
                            <label for="fim" class="text-[12.75px] font-semibold text-ink-90">
                                Data de término
                            </label>
                            <DatePicker
                                id="fim"
                                v-model="form.data_fim"
                                date-format="dd/mm/yy"
                                :min-date="form.data_inicio ?? undefined"
                                placeholder="Opcional"
                                show-icon
                                show-button-bar
                                icon-display="input"
                                class="w-full"
                                :invalid="!!form.errors.data_fim"
                            />
                            <span class="text-[11.5px] text-ink-55">
                                Não pode ser anterior ao início
                            </span>
                            <Message
                                v-if="form.errors.data_fim"
                                severity="error"
                                size="small"
                                variant="simple"
                            >
                                {{ form.errors.data_fim }}
                            </Message>
                        </div>

                        <div class="flex flex-col gap-1.5">
                            <label for="status" class="text-[12.75px] font-semibold text-ink-90">
                                Status <span class="text-laranja-600">●</span>
                            </label>
                            <Select
                                id="status"
                                v-model="form.status"
                                :options="opcoes.status"
                                option-label="label"
                                option-value="value"
                                class="w-full"
                                :invalid="!!form.errors.status"
                            />
                            <Message
                                v-if="form.errors.status"
                                severity="error"
                                size="small"
                                variant="simple"
                            >
                                {{ form.errors.status }}
                            </Message>
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
                            <Message
                                v-if="form.errors.observacoes"
                                severity="error"
                                size="small"
                                variant="simple"
                            >
                                {{ form.errors.observacoes }}
                            </Message>
                        </div>
                    </div>

                    <Aviso v-if="ehRecorrente && form.status === 'ativo'" class="mt-4">
                        Enquanto estiver <strong>Ativo</strong>, este contrato cria pagamentos
                        sozinho às 06:00, alguns dias antes de cada vencimento. Suspender ou
                        encerrar interrompe a geração sem apagar o que já foi lançado.
                    </Aviso>
                </div>

                <!-- Ações -->
                <div class="rounded-lg border border-ink-8 bg-white shadow-card">
                    <div class="flex justify-end gap-2.5 px-5 py-4">
                        <Link :href="route('contratos.index')">
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
                            :label="editando ? 'Salvar alterações' : 'Salvar contrato'"
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
