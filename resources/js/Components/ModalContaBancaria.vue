<script setup lang="ts">
import { computed, watch } from 'vue';
import { useForm } from '@inertiajs/vue3';
import Dialog from 'primevue/dialog';
import InputText from 'primevue/inputtext';
import Select from 'primevue/select';
import Checkbox from 'primevue/checkbox';
import Button from 'primevue/button';
import Message from 'primevue/message';
import Aviso from '@/Components/Aviso.vue';
import Icone from '@/Components/Icone.vue';
import { useFormato } from '@/Composables/useFormato';
import type { ContaBancaria, Opcao } from '@/types';

/**
 * Conta bancária e chave Pix, compartilhada entre colaborador e fornecedor
 * (regra 3.2). Serve cadastro e edição: quando `conta` vem preenchida, salva por
 * PUT em contas.update; quando vem nula, por POST em contas.store.
 */
const props = defineProps<{
    visivel: boolean;
    tipoBeneficiario: 'colaborador' | 'fornecedor';
    beneficiarioId: number;
    conta: ContaBancaria | null;
    opcoes: { tipoConta: Opcao[]; tipoChavePix: Opcao[] };
    /** Quando é a única conta, o backend a mantém principal de todo jeito. */
    unicaConta: boolean;
}>();

const emit = defineEmits<{ 'update:visivel': [valor: boolean] }>();

const { formatarDocumento } = useFormato();

const editando = computed(() => props.conta !== null);

const form = useForm({
    banco: '',
    codigo_banco: '',
    agencia: '',
    conta: '',
    digito: '',
    tipo_conta: 'corrente',
    titular_nome: '',
    titular_documento: '',
    tipo_chave_pix: null as string | null,
    chave_pix: '',
    principal: false,
});

const carregar = () => {
    form.clearErrors();

    if (!props.conta) {
        form.reset();

        return;
    }

    form.banco = props.conta.banco;
    form.codigo_banco = props.conta.codigo_banco ?? '';
    form.agencia = props.conta.agencia;
    form.conta = props.conta.conta;
    // Comparar com null: o dígito "0" é falsy e sumiria do formulário.
    form.digito = props.conta.digito ?? '';
    form.tipo_conta = props.conta.tipo_conta;
    form.titular_nome = props.conta.titular_nome;
    form.titular_documento = formatarDocumento(props.conta.titular_documento);
    form.tipo_chave_pix = props.conta.tipo_chave_pix;
    form.chave_pix = props.conta.chave_pix ?? '';
    form.principal = props.conta.principal;
};

watch(() => props.visivel, (aberto) => aberto && carregar());

/* ------------------------------------------------------------------
 * Chave Pix: formato por tipo
 * ---------------------------------------------------------------- */

/**
 * Espelha TipoChavePix::formatoValido. É só formato — nem aqui nem no backend
 * existe consulta ao DICT, então uma chave bem formada ainda pode não existir.
 */
const FORMATO_PIX: Record<string, (chave: string) => boolean> = {
    cpf: (chave) => chave.replace(/\D/g, '').length === 11,
    cnpj: (chave) => chave.replace(/\D/g, '').length === 14,
    email: (chave) => /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(chave.trim()),
    telefone: (chave) => /^\+55\d{10,11}$/.test(chave.replace(/[\s()-]/g, '')),
    aleatoria: (chave) =>
        /^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i.test(
            chave.trim(),
        ),
};

const EXEMPLO_PIX: Record<string, string> = {
    cpf: '529.982.247-25',
    cnpj: '12.345.678/0001-95',
    email: 'financeiro@empresa.com.br',
    telefone: '+5598991234455',
    aleatoria: '3f1b6c2e-9a4d-4b17-8f0e-2c5d7a9b1e34',
};

const chavePreenchida = computed(() => form.chave_pix.trim().length > 0);

const chaveInvalida = computed(() => {
    if (!form.tipo_chave_pix || !chavePreenchida.value) return false;

    return !(FORMATO_PIX[form.tipo_chave_pix]?.(form.chave_pix) ?? true);
});

// Trocar o tipo revalida a chave existente; limpar o tipo limpa a chave junto,
// senão o backend recusa por required_with e o erro aparece longe da causa.
watch(
    () => form.tipo_chave_pix,
    (novo) => {
        if (!novo) {
            form.chave_pix = '';
        }
    },
);

const fechar = () => emit('update:visivel', false);

const salvar = () => {
    const opcoes = {
        preserveScroll: true,
        onSuccess: () => fechar(),
    };

    if (props.conta) {
        form.put(
            route('contas.update', {
                tipoBeneficiario: props.tipoBeneficiario,
                beneficiarioId: props.beneficiarioId,
                conta: props.conta.id,
            }),
            opcoes,
        );

        return;
    }

    form.post(
        route('contas.store', {
            tipoBeneficiario: props.tipoBeneficiario,
            beneficiarioId: props.beneficiarioId,
        }),
        opcoes,
    );
};
</script>

<template>
    <Dialog
        :visible="visivel"
        modal
        :draggable="false"
        class="w-full max-w-[620px]"
        @update:visible="emit('update:visivel', $event)"
    >
        <template #header>
            <div class="min-w-0">
                <h3 class="text-[15.5px] font-semibold">
                    {{ editando ? 'Editar conta bancária' : 'Nova conta bancária' }}
                </h3>
                <p class="mt-[3px] text-[12.25px] text-ink-55">
                    Destino de pagamento do
                    {{ tipoBeneficiario === 'colaborador' ? 'colaborador' : 'fornecedor' }}
                </p>
            </div>
        </template>

        <form id="form-conta-bancaria" class="grid gap-3.5 sm:grid-cols-6" @submit.prevent="salvar">
            <!-- Banco -->
            <div class="flex flex-col gap-1.5 sm:col-span-4">
                <label for="banco" class="text-[12.75px] font-semibold text-ink-90">
                    Banco <span class="text-laranja-600">●</span>
                </label>
                <InputText
                    id="banco"
                    v-model="form.banco"
                    maxlength="255"
                    placeholder="Ex.: Itaú Unibanco"
                    class="w-full"
                    :invalid="!!form.errors.banco"
                />
                <Message v-if="form.errors.banco" severity="error" size="small" variant="simple">
                    {{ form.errors.banco }}
                </Message>
            </div>

            <div class="flex flex-col gap-1.5 sm:col-span-2">
                <label for="codigo-banco" class="text-[12.75px] font-semibold text-ink-90">
                    Código
                </label>
                <InputText
                    id="codigo-banco"
                    v-model="form.codigo_banco"
                    maxlength="5"
                    placeholder="341"
                    class="w-full mono"
                    :invalid="!!form.errors.codigo_banco"
                />
                <Message
                    v-if="form.errors.codigo_banco"
                    severity="error"
                    size="small"
                    variant="simple"
                >
                    {{ form.errors.codigo_banco }}
                </Message>
            </div>

            <!-- Agência, conta, dígito -->
            <div class="flex flex-col gap-1.5 sm:col-span-2">
                <label for="agencia" class="text-[12.75px] font-semibold text-ink-90">
                    Agência <span class="text-laranja-600">●</span>
                </label>
                <InputText
                    id="agencia"
                    v-model="form.agencia"
                    maxlength="10"
                    class="w-full mono"
                    :invalid="!!form.errors.agencia"
                />
                <Message v-if="form.errors.agencia" severity="error" size="small" variant="simple">
                    {{ form.errors.agencia }}
                </Message>
            </div>

            <div class="flex flex-col gap-1.5 sm:col-span-2">
                <label for="conta" class="text-[12.75px] font-semibold text-ink-90">
                    Conta <span class="text-laranja-600">●</span>
                </label>
                <InputText
                    id="conta"
                    v-model="form.conta"
                    maxlength="20"
                    class="w-full mono"
                    :invalid="!!form.errors.conta"
                />
                <Message v-if="form.errors.conta" severity="error" size="small" variant="simple">
                    {{ form.errors.conta }}
                </Message>
            </div>

            <div class="flex flex-col gap-1.5 sm:col-span-2">
                <label for="digito" class="text-[12.75px] font-semibold text-ink-90">
                    Dígito
                </label>
                <InputText
                    id="digito"
                    v-model="form.digito"
                    maxlength="2"
                    class="w-full mono"
                    :invalid="!!form.errors.digito"
                />
                <Message v-if="form.errors.digito" severity="error" size="small" variant="simple">
                    {{ form.errors.digito }}
                </Message>
            </div>

            <div class="flex flex-col gap-1.5 sm:col-span-6">
                <label for="tipo-conta" class="text-[12.75px] font-semibold text-ink-90">
                    Tipo de conta <span class="text-laranja-600">●</span>
                </label>
                <Select
                    id="tipo-conta"
                    v-model="form.tipo_conta"
                    :options="opcoes.tipoConta"
                    option-label="label"
                    option-value="value"
                    class="w-full"
                    :invalid="!!form.errors.tipo_conta"
                />
                <Message
                    v-if="form.errors.tipo_conta"
                    severity="error"
                    size="small"
                    variant="simple"
                >
                    {{ form.errors.tipo_conta }}
                </Message>
            </div>

            <!-- Titular -->
            <div class="flex flex-col gap-1.5 sm:col-span-4">
                <label for="titular-nome" class="text-[12.75px] font-semibold text-ink-90">
                    Nome do titular <span class="text-laranja-600">●</span>
                </label>
                <InputText
                    id="titular-nome"
                    v-model="form.titular_nome"
                    maxlength="255"
                    class="w-full"
                    :invalid="!!form.errors.titular_nome"
                />
                <Message
                    v-if="form.errors.titular_nome"
                    severity="error"
                    size="small"
                    variant="simple"
                >
                    {{ form.errors.titular_nome }}
                </Message>
            </div>

            <div class="flex flex-col gap-1.5 sm:col-span-2">
                <label for="titular-documento" class="text-[12.75px] font-semibold text-ink-90">
                    CPF/CNPJ <span class="text-laranja-600">●</span>
                </label>
                <InputText
                    id="titular-documento"
                    v-model="form.titular_documento"
                    class="w-full mono"
                    placeholder="CPF ou CNPJ"
                    :invalid="!!form.errors.titular_documento"
                />
                <Message
                    v-if="form.errors.titular_documento"
                    severity="error"
                    size="small"
                    variant="simple"
                >
                    {{ form.errors.titular_documento }}
                </Message>
            </div>

            <!-- Pix -->
            <div class="flex flex-col gap-1.5 sm:col-span-2">
                <label for="tipo-chave" class="text-[12.75px] font-semibold text-ink-90">
                    Tipo da chave Pix
                </label>
                <Select
                    id="tipo-chave"
                    v-model="form.tipo_chave_pix"
                    :options="opcoes.tipoChavePix"
                    option-label="label"
                    option-value="value"
                    placeholder="Sem Pix"
                    show-clear
                    class="w-full"
                    :invalid="!!form.errors.tipo_chave_pix"
                />
                <Message
                    v-if="form.errors.tipo_chave_pix"
                    severity="error"
                    size="small"
                    variant="simple"
                >
                    {{ form.errors.tipo_chave_pix }}
                </Message>
            </div>

            <div class="flex flex-col gap-1.5 sm:col-span-4">
                <label for="chave-pix" class="text-[12.75px] font-semibold text-ink-90">
                    Chave Pix
                </label>
                <InputText
                    id="chave-pix"
                    v-model="form.chave_pix"
                    maxlength="255"
                    :disabled="!form.tipo_chave_pix"
                    :placeholder="
                        form.tipo_chave_pix
                            ? EXEMPLO_PIX[form.tipo_chave_pix]
                            : 'Escolha o tipo da chave antes'
                    "
                    class="w-full mono"
                    :invalid="!!form.errors.chave_pix || chaveInvalida"
                />
                <Message
                    v-if="form.errors.chave_pix"
                    severity="error"
                    size="small"
                    variant="simple"
                >
                    {{ form.errors.chave_pix }}
                </Message>
                <Message
                    v-else-if="chaveInvalida"
                    severity="error"
                    size="small"
                    variant="simple"
                >
                    Formato inválido para este tipo de chave.
                </Message>
            </div>

            <div class="sm:col-span-6">
                <Aviso icone="zap">
                    <strong>Só o formato da chave é conferido.</strong> O sistema não consulta o
                    DICT, então uma chave bem escrita ainda pode não existir ou pertencer a
                    outra pessoa. Confira no extrato depois da primeira transferência.
                </Aviso>
            </div>

            <!-- Principal -->
            <div class="flex flex-col gap-1.5 sm:col-span-6">
                <label class="flex items-center gap-2 text-[13px]">
                    <Checkbox
                        v-model="form.principal"
                        input-id="conta-principal"
                        binary
                        :disabled="unicaConta"
                    />
                    <span>Definir como conta principal (destino padrão)</span>
                </label>
                <span v-if="unicaConta" class="text-[11.5px] text-ink-55">
                    Sendo a única conta, ela é a principal — o beneficiário ficaria sem destino
                    padrão de outra forma.
                </span>
                <span v-else-if="conta?.principal" class="text-[11.5px] text-ink-55">
                    Para trocar a principal, marque outra conta. Desmarcar aqui não tem efeito.
                </span>
            </div>
        </form>

        <template #footer>
            <Button
                label="Cancelar"
                severity="secondary"
                outlined
                size="small"
                :disabled="form.processing"
                @click="fechar"
            />
            <Button
                type="submit"
                form="form-conta-bancaria"
                :label="editando ? 'Salvar alterações' : 'Salvar conta'"
                size="small"
                :loading="form.processing"
                :disabled="chaveInvalida"
            >
                <template #icon><Icone nome="checkCircle" :tamanho="16" /></template>
            </Button>
        </template>
    </Dialog>
</template>
