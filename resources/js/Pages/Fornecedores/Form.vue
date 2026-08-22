<script setup lang="ts">
import { computed, watch } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import InputText from 'primevue/inputtext';
import InputMask from 'primevue/inputmask';
import Select from 'primevue/select';
import Textarea from 'primevue/textarea';
import Button from 'primevue/button';
import Message from 'primevue/message';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import CabecalhoPagina from '@/Components/CabecalhoPagina.vue';
import Icone from '@/Components/Icone.vue';
import { useFormato } from '@/Composables/useFormato';
import type { Fornecedor, Opcao } from '@/types';

const props = defineProps<{
    fornecedor: Fornecedor | null;
    opcoes: { tipoPessoa: Opcao[]; tipoFornecedor: Opcao[]; status: Opcao[] };
}>();

const { formatarDocumento, cpfValido, cnpjValido } = useFormato();

const editando = props.fornecedor !== null;

const TIPOS_PESSOA = [
    { valor: 'pf' as const, rotulo: 'Pessoa Física', icone: 'user' },
    { valor: 'pj' as const, rotulo: 'Pessoa Jurídica', icone: 'building' },
];

const form = useForm({
    tipo_pessoa: (props.fornecedor?.tipo_pessoa as 'pf' | 'pj') ?? 'pj',
    razao_social: props.fornecedor?.razao_social ?? '',
    nome_fantasia: props.fornecedor?.nome_fantasia ?? '',
    // O banco guarda só dígitos; a máscara trabalha com o documento formatado.
    documento:
        formatarDocumento(props.fornecedor?.documento) === '—'
            ? ''
            : formatarDocumento(props.fornecedor?.documento),
    tipo_fornecedor: props.fornecedor?.tipo_fornecedor ?? 'servico',
    email: props.fornecedor?.email ?? '',
    telefone: props.fornecedor?.telefone ?? '',
    endereco: props.fornecedor?.endereco ?? '',
    status: props.fornecedor?.status ?? 'ativo',
    observacoes: props.fornecedor?.observacoes ?? '',
});

/* ------------------------------------------------------------------
 * O tipo de pessoa comanda a máscara e a validação do documento
 * ---------------------------------------------------------------- */
const ehPessoaJuridica = computed(() => form.tipo_pessoa === 'pj');

const mascaraDocumento = computed(() =>
    ehPessoaJuridica.value ? '99.999.999/9999-99' : '999.999.999-99',
);

const exemploDocumento = computed(() =>
    ehPessoaJuridica.value ? '00.000.000/0000-00' : '000.000.000-00',
);

const digitosNecessarios = computed(() => (ehPessoaJuridica.value ? 14 : 11));

const documentoCompleto = computed(
    () => form.documento.replace(/\D/g, '').length === digitosNecessarios.value,
);

const documentoInvalido = computed(() => {
    if (!documentoCompleto.value) return false;

    return ehPessoaJuridica.value ? !cnpjValido(form.documento) : !cpfValido(form.documento);
});

/**
 * Trocar o tipo de pessoa limpa o documento. A máscara de CNPJ aplicada sobre
 * dígitos de CPF produz um número que parece válido e não é — e o backend valida
 * conforme o tipo escolhido, então o erro só apareceria no submit.
 */
const trocarTipoPessoa = (tipo: 'pf' | 'pj') => {
    if (form.tipo_pessoa === tipo) return;

    form.tipo_pessoa = tipo;
    form.documento = '';
    form.clearErrors('documento');
};

// Editando, o rótulo do campo acompanha o tipo já gravado.
watch(
    () => form.tipo_pessoa,
    () => form.clearErrors('documento'),
);

const enviar = () => {
    if (editando && props.fornecedor) {
        form.put(route('fornecedores.update', props.fornecedor.id));

        return;
    }

    form.post(route('fornecedores.store'));
};
</script>

<template>
    <Head :title="editando ? 'Editar fornecedor' : 'Novo fornecedor'" />

    <AuthenticatedLayout>
        <template #header>
            <CabecalhoPagina
                :titulo="editando ? 'Editar fornecedor' : 'Novo fornecedor'"
                :descricao="editando ? fornecedor?.razao_social : 'Prestador de serviço ou fornecedor de produto'"
            />
        </template>

        <div class="max-w-[960px]">
            <Link
                :href="route('fornecedores.index')"
                class="mb-3 inline-flex items-center gap-1.5 rounded-lg px-2.5 py-1.5 text-[12.25px] font-semibold text-ink-70 hover:bg-ink-8"
            >
                <Icone nome="chevronLeft" :tamanho="15" />
                Voltar para fornecedores
            </Link>

            <form class="space-y-4" @submit.prevent="enviar">
                <div class="rounded-lg border border-ink-8 bg-white p-5 shadow-card">
                    <h3 class="mb-1 text-[14px] font-semibold">Dados do fornecedor</h3>
                    <p class="mb-4 text-[12px] text-ink-55">
                        O tipo de pessoa muda a máscara e a validação do documento abaixo.
                    </p>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div class="flex flex-col gap-1.5 sm:col-span-2">
                            <label class="text-[12.75px] font-semibold text-ink-90">
                                Tipo de pessoa <span class="text-laranja-600">●</span>
                            </label>
                            <div class="flex gap-0.5 rounded-lg bg-ink-8 p-[3px]">
                                <button
                                    v-for="tipo in TIPOS_PESSOA"
                                    :key="tipo.valor"
                                    type="button"
                                    class="flex flex-1 items-center justify-center gap-1.5 rounded-md py-2 text-[13px] font-semibold transition-colors"
                                    :class="
                                        form.tipo_pessoa === tipo.valor
                                            ? 'bg-white text-ink shadow-card'
                                            : 'text-ink-55 hover:text-ink-70'
                                    "
                                    @click="trocarTipoPessoa(tipo.valor)"
                                >
                                    <Icone :nome="tipo.icone" :tamanho="15" />
                                    {{ tipo.rotulo }}
                                </button>
                            </div>
                            <Message
                                v-if="form.errors.tipo_pessoa"
                                severity="error"
                                size="small"
                                variant="simple"
                            >
                                {{ form.errors.tipo_pessoa }}
                            </Message>
                        </div>

                        <div class="flex flex-col gap-1.5 sm:col-span-2">
                            <label for="razao" class="text-[12.75px] font-semibold text-ink-90">
                                Razão social / nome <span class="text-laranja-600">●</span>
                            </label>
                            <InputText
                                id="razao"
                                v-model="form.razao_social"
                                maxlength="255"
                                class="w-full"
                                :invalid="!!form.errors.razao_social"
                            />
                            <Message
                                v-if="form.errors.razao_social"
                                severity="error"
                                size="small"
                                variant="simple"
                            >
                                {{ form.errors.razao_social }}
                            </Message>
                        </div>

                        <div class="flex flex-col gap-1.5">
                            <label for="fantasia" class="text-[12.75px] font-semibold text-ink-90">
                                Nome fantasia
                            </label>
                            <InputText
                                id="fantasia"
                                v-model="form.nome_fantasia"
                                maxlength="255"
                                class="w-full"
                                :invalid="!!form.errors.nome_fantasia"
                            />
                            <Message
                                v-if="form.errors.nome_fantasia"
                                severity="error"
                                size="small"
                                variant="simple"
                            >
                                {{ form.errors.nome_fantasia }}
                            </Message>
                        </div>

                        <div class="flex flex-col gap-1.5">
                            <label for="documento" class="text-[12.75px] font-semibold text-ink-90">
                                {{ ehPessoaJuridica ? 'CNPJ' : 'CPF' }}
                                <span class="text-laranja-600">●</span>
                            </label>
                            <InputMask
                                id="documento"
                                :key="form.tipo_pessoa"
                                v-model="form.documento"
                                :mask="mascaraDocumento"
                                :placeholder="exemploDocumento"
                                class="w-full"
                                input-class="mono"
                                :invalid="!!form.errors.documento || documentoInvalido"
                            />
                            <span class="text-[11.5px] text-azul-600">
                                Máscara de {{ ehPessoaJuridica ? 'CNPJ' : 'CPF' }} — tipo de
                                pessoa é {{ ehPessoaJuridica ? 'Jurídica' : 'Física' }}
                            </span>
                            <Message
                                v-if="form.errors.documento"
                                severity="error"
                                size="small"
                                variant="simple"
                            >
                                {{ form.errors.documento }}
                            </Message>
                            <Message
                                v-else-if="documentoInvalido"
                                severity="error"
                                size="small"
                                variant="simple"
                            >
                                Dígito verificador não confere — revise o número.
                            </Message>
                        </div>

                        <div class="flex flex-col gap-1.5">
                            <label for="tipo-forn" class="text-[12.75px] font-semibold text-ink-90">
                                Tipo de fornecedor <span class="text-laranja-600">●</span>
                            </label>
                            <Select
                                id="tipo-forn"
                                v-model="form.tipo_fornecedor"
                                :options="opcoes.tipoFornecedor"
                                option-label="label"
                                option-value="value"
                                class="w-full"
                                :invalid="!!form.errors.tipo_fornecedor"
                            />
                            <Message
                                v-if="form.errors.tipo_fornecedor"
                                severity="error"
                                size="small"
                                variant="simple"
                            >
                                {{ form.errors.tipo_fornecedor }}
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
                            <span v-if="form.status === 'inativo'" class="text-[11.5px] text-ink-55">
                                Inativo mantém o histórico, mas bloqueia novos lançamentos
                            </span>
                            <Message
                                v-if="form.errors.status"
                                severity="error"
                                size="small"
                                variant="simple"
                            >
                                {{ form.errors.status }}
                            </Message>
                        </div>

                        <div class="flex flex-col gap-1.5">
                            <label for="email" class="text-[12.75px] font-semibold text-ink-90">
                                E-mail
                            </label>
                            <InputText
                                id="email"
                                v-model="form.email"
                                type="email"
                                maxlength="255"
                                class="w-full"
                                :invalid="!!form.errors.email"
                            />
                            <Message
                                v-if="form.errors.email"
                                severity="error"
                                size="small"
                                variant="simple"
                            >
                                {{ form.errors.email }}
                            </Message>
                        </div>

                        <div class="flex flex-col gap-1.5">
                            <label for="telefone" class="text-[12.75px] font-semibold text-ink-90">
                                Telefone
                            </label>
                            <InputText
                                id="telefone"
                                v-model="form.telefone"
                                maxlength="20"
                                placeholder="(00) 0000-0000"
                                class="w-full"
                                :invalid="!!form.errors.telefone"
                            />
                            <Message
                                v-if="form.errors.telefone"
                                severity="error"
                                size="small"
                                variant="simple"
                            >
                                {{ form.errors.telefone }}
                            </Message>
                        </div>

                        <div class="flex flex-col gap-1.5 sm:col-span-2">
                            <label for="endereco" class="text-[12.75px] font-semibold text-ink-90">
                                Endereço
                            </label>
                            <InputText
                                id="endereco"
                                v-model="form.endereco"
                                maxlength="255"
                                class="w-full"
                                :invalid="!!form.errors.endereco"
                            />
                            <Message
                                v-if="form.errors.endereco"
                                severity="error"
                                size="small"
                                variant="simple"
                            >
                                {{ form.errors.endereco }}
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
                </div>

                <!-- Ações -->
                <div class="rounded-lg border border-ink-8 bg-white shadow-card">
                    <div class="flex justify-end gap-2.5 px-5 py-4">
                        <Link :href="route('fornecedores.index')">
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
                            :label="editando ? 'Salvar alterações' : 'Cadastrar'"
                            size="small"
                            :loading="form.processing"
                            :disabled="documentoInvalido"
                        >
                            <template #icon><Icone nome="checkCircle" :tamanho="16" /></template>
                        </Button>
                    </div>
                </div>
            </form>
        </div>
    </AuthenticatedLayout>
</template>
