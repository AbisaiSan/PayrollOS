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

interface ColaboradorOpcao {
    id: number;
    nome: string;
    departamento: string;
}

interface ContaOpcao {
    id: number;
    resumo: string;
    principal: boolean;
}

const props = defineProps<{
    reembolso: (Record<string, unknown> & { id: number }) | null;
    opcoes: { categorias: Opcao[]; colaboradores: ColaboradorOpcao[] };
}>();

const { paraDate, paraIso } = useFormato();

const editando = props.reembolso !== null;

/** O comprovante aceito pelo ReembolsoRequest (config payrollos.anexos). */
const EXTENSOES = '.pdf,.jpg,.jpeg,.png,.xml';
const TAMANHO_MAXIMO_MB = 10;

const hoje = new Date();

const form = useForm({
    colaborador_id: (props.reembolso?.colaborador_id as number | null) ?? null,
    conta_bancaria_id: (props.reembolso?.conta_bancaria_id as number | null) ?? null,
    descricao: (props.reembolso?.descricao as string) ?? '',
    categoria: (props.reembolso?.categoria as string | null) ?? null,
    valor: props.reembolso ? Number(props.reembolso.valor) : null,
    data_solicitacao: paraDate(props.reembolso?.data_solicitacao as string | null) ?? hoje,
    observacoes: (props.reembolso?.observacoes as string) ?? '',
    comprovante: null as File | null,
});

/* ------------------------------------------------------------------
 * O colaborador escolhido filtra as contas de destino
 * ---------------------------------------------------------------- */

/**
 * Mesma rota que o formulário de pagamento usa: ela já devolve só contas ativas,
 * que é o que o serviço aceita como destino. Buscar o colaborador em si não
 * precisa de rota — a lista de ativos já vem pronta nas opções do formulário.
 */
const contas = ref<ContaOpcao[]>([]);
const carregandoContas = ref(false);

const carregarContas = async (colaboradorId: number) => {
    carregandoContas.value = true;

    try {
        const resposta = await fetch(
            route('beneficiarios.dados', { tipo: 'colaborador', id: colaboradorId }),
            { headers: { Accept: 'application/json' } },
        );
        const corpo = await resposta.json();

        contas.value = corpo.contas ?? [];

        // Conta principal já vem selecionada: é o destino padrão do colaborador.
        const principal = contas.value.find((c) => c.principal);
        if (principal && !form.conta_bancaria_id) {
            form.conta_bancaria_id = principal.id;
        }
    } finally {
        carregandoContas.value = false;
    }
};

watch(
    () => form.colaborador_id,
    (novo) => {
        contas.value = [];
        form.conta_bancaria_id = null;

        if (novo) {
            carregarContas(novo);
        }
    },
);

// Ao editar, as contas já precisam estar carregadas na abertura.
if (editando && form.colaborador_id) {
    carregarContas(form.colaborador_id);
}

const semContaCadastrada = computed(
    () => !!form.colaborador_id && !carregandoContas.value && contas.value.length === 0,
);

/* ------------------------------------------------------------------
 * Comprovante
 * ---------------------------------------------------------------- */
const arrastando = ref(false);
const campoArquivo = ref<HTMLInputElement | null>(null);

const nomeComprovante = computed(() => form.comprovante?.name ?? null);

const receberArquivo = (arquivo: File | null | undefined) => {
    form.comprovante = arquivo ?? null;
    form.clearErrors('comprovante');
};

const aoSoltar = (evento: DragEvent) => {
    arrastando.value = false;
    receberArquivo(evento.dataTransfer?.files?.[0]);
};

const removerComprovante = () => {
    receberArquivo(null);

    // O input guarda o arquivo anterior; sem limpar, reescolher o mesmo arquivo
    // não dispara change e o campo fica visualmente vazio com o anexo preso.
    if (campoArquivo.value) {
        campoArquivo.value.value = '';
    }
};

/* ------------------------------------------------------------------
 * Envio
 * ---------------------------------------------------------------- */
const enviar = () => {
    const dados = form.transform((campos) => ({
        ...campos,
        data_solicitacao: paraIso(campos.data_solicitacao),
        // Anexo é multipart, e multipart não sobe em PUT: o update vai por POST
        // com method spoofing, que é como o Laravel espera receber arquivo.
        ...(editando ? { _method: 'put' } : {}),
    }));

    if (editando && props.reembolso) {
        // forceFormData mesmo sem arquivo escolhido: sem multipart o `_method` iria
        // no corpo JSON, onde o Laravel não o lê, e o POST bateria numa rota que
        // não existe. Com FormData o spoofing funciona nos dois casos.
        dados.post(route('reembolsos.update', props.reembolso.id), { forceFormData: true });

        return;
    }

    dados.post(route('reembolsos.store'));
};
</script>

<template>
    <Head :title="editando ? 'Editar solicitação' : 'Nova solicitação de reembolso'" />

    <AuthenticatedLayout>
        <template #header>
            <CabecalhoPagina
                :titulo="editando ? 'Editar solicitação' : 'Nova solicitação de reembolso'"
                descricao="Despesa adiantada pelo colaborador, enviada para aprovação"
            />
        </template>

        <div class="max-w-[960px]">
            <Link
                :href="route('reembolsos.index')"
                class="mb-3 inline-flex items-center gap-1.5 rounded-lg px-2.5 py-1.5 text-[12.25px] font-semibold text-ink-70 hover:bg-ink-8"
            >
                <Icone nome="chevronLeft" :tamanho="15" />
                Voltar para reembolsos
            </Link>

            <Aviso class="mb-4">
                <strong>A solicitação nasce Pendente.</strong> Ela ainda passa por aprovação, e o
                pagamento é registrado depois, na tela de detalhe.
            </Aviso>

            <form class="space-y-4" @submit.prevent="enviar">
                <div class="rounded-lg border border-ink-8 bg-white p-5 shadow-card">
                    <h3 class="mb-4 text-[14px] font-semibold">Solicitação de reembolso</h3>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div class="flex flex-col gap-1.5">
                            <label for="colaborador" class="text-[12.75px] font-semibold text-ink-90">
                                Colaborador <span class="text-laranja-600">●</span>
                            </label>
                            <Select
                                id="colaborador"
                                v-model="form.colaborador_id"
                                :options="opcoes.colaboradores"
                                option-label="nome"
                                option-value="id"
                                placeholder="Selecione"
                                filter
                                filter-placeholder="Buscar colaborador…"
                                class="w-full"
                                :invalid="!!form.errors.colaborador_id"
                            >
                                <template #option="{ option }">
                                    <div class="flex flex-col">
                                        <span class="text-[13px] font-medium">{{ option.nome }}</span>
                                        <span class="text-[11.5px] text-ink-55">
                                            {{ option.departamento }}
                                        </span>
                                    </div>
                                </template>
                            </Select>
                            <span class="text-[11.5px] text-ink-55">Somente colaboradores ativos</span>
                            <Message
                                v-if="form.errors.colaborador_id"
                                severity="error"
                                size="small"
                                variant="simple"
                            >
                                {{ form.errors.colaborador_id }}
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
                                    form.colaborador_id
                                        ? 'Selecione a conta'
                                        : 'Escolha o colaborador antes'
                                "
                                :disabled="!form.colaborador_id || carregandoContas || !contas.length"
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
                            <span class="text-[11.5px] text-ink-55">
                                Só contas ativas · a principal já vem escolhida
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
                                placeholder="Ex.: Passagem aérea — visita técnica"
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
                            <label for="categoria" class="text-[12.75px] font-semibold text-ink-90">
                                Categoria da despesa <span class="text-laranja-600">●</span>
                            </label>
                            <Select
                                id="categoria"
                                v-model="form.categoria"
                                :options="opcoes.categorias"
                                option-label="label"
                                option-value="value"
                                placeholder="Selecione"
                                class="w-full"
                                :invalid="!!form.errors.categoria"
                            />
                            <Message
                                v-if="form.errors.categoria"
                                severity="error"
                                size="small"
                                variant="simple"
                            >
                                {{ form.errors.categoria }}
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
                            <label for="solicitacao" class="text-[12.75px] font-semibold text-ink-90">
                                Data da solicitação <span class="text-laranja-600">●</span>
                            </label>
                            <DatePicker
                                id="solicitacao"
                                v-model="form.data_solicitacao"
                                date-format="dd/mm/yy"
                                :max-date="hoje"
                                show-icon
                                icon-display="input"
                                class="w-full"
                                :invalid="!!form.errors.data_solicitacao"
                            />
                            <span class="text-[11.5px] text-ink-55">
                                A despesa já aconteceu — não pode ser futura
                            </span>
                            <Message
                                v-if="form.errors.data_solicitacao"
                                severity="error"
                                size="small"
                                variant="simple"
                            >
                                {{ form.errors.data_solicitacao }}
                            </Message>
                        </div>

                        <!-- Comprovante -->
                        <div class="flex flex-col gap-1.5">
                            <label for="comprovante" class="text-[12.75px] font-semibold text-ink-90">
                                Comprovante
                            </label>

                            <input
                                id="comprovante"
                                ref="campoArquivo"
                                type="file"
                                class="sr-only"
                                :accept="EXTENSOES"
                                @change="
                                    receberArquivo(
                                        ($event.target as HTMLInputElement).files?.[0],
                                    )
                                "
                            />

                            <div
                                v-if="nomeComprovante"
                                class="flex items-center justify-between gap-2 rounded-md border border-ink-8 bg-white px-3.5 py-[11px]"
                            >
                                <div class="flex min-w-0 items-center gap-2.5">
                                    <Icone
                                        nome="paperclip"
                                        :tamanho="16"
                                        class="shrink-0 text-ink-55"
                                    />
                                    <span class="truncate text-[13px] font-medium">
                                        {{ nomeComprovante }}
                                    </span>
                                </div>
                                <button
                                    type="button"
                                    class="shrink-0 rounded-lg p-1.5 text-ink-55 hover:bg-ink-8 hover:text-perigo"
                                    title="Remover comprovante"
                                    @click="removerComprovante"
                                >
                                    <Icone nome="x" :tamanho="15" />
                                </button>
                            </div>

                            <label
                                v-else
                                for="comprovante"
                                class="flex cursor-pointer items-center justify-center gap-2 rounded-md border border-dashed px-3.5 py-[11px] text-[12.75px] transition-colors"
                                :class="
                                    arrastando
                                        ? 'border-laranja-500 bg-laranja-50 text-laranja-700'
                                        : 'border-ink-16 text-ink-55 hover:border-ink-35 hover:text-ink-70'
                                "
                                @dragover.prevent="arrastando = true"
                                @dragleave.prevent="arrastando = false"
                                @drop.prevent="aoSoltar"
                            >
                                <Icone nome="upload" :tamanho="15" />
                                Arraste ou clique para anexar
                            </label>

                            <span class="text-[11.5px] text-ink-55">
                                pdf, jpg, jpeg, png ou xml · máx. {{ TAMANHO_MAXIMO_MB }} MB
                            </span>
                            <span v-if="editando" class="text-[11.5px] text-azul-600">
                                Anexar aqui acrescenta um comprovante; os já enviados continuam na
                                solicitação.
                            </span>
                            <Message
                                v-if="form.errors.comprovante"
                                severity="error"
                                size="small"
                                variant="simple"
                            >
                                {{ form.errors.comprovante }}
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

                    <Aviso v-if="semContaCadastrada" tom="atencao" icone="alertTriangle" class="mt-4">
                        Este colaborador não tem conta ativa cadastrada. A solicitação pode seguir,
                        mas alguém terá de informar o destino antes de registrar o pagamento.
                    </Aviso>
                </div>

                <!-- Ações -->
                <div class="rounded-lg border border-ink-8 bg-white shadow-card">
                    <div class="flex justify-end gap-2.5 px-5 py-4">
                        <Link :href="route('reembolsos.index')">
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
                            :label="editando ? 'Salvar alterações' : 'Enviar solicitação'"
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
