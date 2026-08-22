<script setup lang="ts">
import { computed, ref } from 'vue';
import { router, useForm } from '@inertiajs/vue3';
import Button from 'primevue/button';
import Dialog from 'primevue/dialog';
import InputText from 'primevue/inputtext';
import Select from 'primevue/select';
import Checkbox from 'primevue/checkbox';
import Message from 'primevue/message';
import { useConfirm } from 'primevue/useconfirm';
import Icone from '@/Components/Icone.vue';
import { usePermissoes } from '@/Composables/usePermissoes';
import { useFormato } from '@/Composables/useFormato';
import type { ContaBancaria, Opcao } from '@/types';

/**
 * Regra 3.2: componente unico para colaborador e fornecedor, ja que a tabela de
 * contas e compartilhada entre os dois.
 */
const props = defineProps<{
    tipoBeneficiario: 'colaborador' | 'fornecedor';
    beneficiarioId: number;
    contas: ContaBancaria[];
    opcoes: { tipoConta: Opcao[]; tipoChavePix: Opcao[] };
}>();

const { pode } = usePermissoes();
const { formatarDocumento } = useFormato();
const confirm = useConfirm();

const dialogAberto = ref(false);

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

/**
 * "Ag. 1234 · C/C 56789-0". Comparar com null/vazio em vez de usar o valor como
 * booleano: o dígito "0" é falsy e a conta sairia como "56789".
 */
const numeroDaConta = (conta: ContaBancaria) => {
    const temDigito = conta.digito !== null && conta.digito !== '';
    const numero = temDigito ? `${conta.conta}-${conta.digito}` : conta.conta;
    const tipo = conta.tipo_conta === 'poupanca' ? 'Poup.' : 'C/C';

    return `Ag. ${conta.agencia} · ${tipo} ${numero}`;
};

const ativas = computed(() => props.contas.filter((conta) => conta.status === 'ativa'));

/**
 * Regra da Seção 10: a principal só pode ser inativada quando não há outra conta
 * ativa para assumir o lugar dela. O serviço recusa de todo jeito; aqui o botão
 * fica desabilitado dizendo o porquê, em vez de sumir sem explicação.
 */
const bloqueiaInativar = (conta: ContaBancaria) =>
    conta.principal && ativas.value.some((outra) => outra.id !== conta.id);

const abrirDialog = () => {
    form.reset();
    form.clearErrors();
    dialogAberto.value = true;
};

const salvar = () => {
    form.post(
        route('contas.store', {
            tipoBeneficiario: props.tipoBeneficiario,
            beneficiarioId: props.beneficiarioId,
        }),
        {
            preserveScroll: true,
            onSuccess: () => {
                dialogAberto.value = false;
            },
        },
    );
};

const parametros = (conta: ContaBancaria) => ({
    tipoBeneficiario: props.tipoBeneficiario,
    beneficiarioId: props.beneficiarioId,
    conta: conta.id,
});

const definirPrincipal = (conta: ContaBancaria) => {
    router.post(route('contas.principal', parametros(conta)), {}, { preserveScroll: true });
};

const inativar = (conta: ContaBancaria) => {
    confirm.require({
        header: 'Inativar conta',
        message:
            'A conta deixa de aparecer para novos lançamentos, mas continua vinculada aos pagamentos já feitos. Contas são inativadas, nunca excluídas.',
        acceptLabel: 'Inativar',
        rejectLabel: 'Voltar',
        acceptProps: { severity: 'danger', size: 'small' },
        rejectProps: { severity: 'secondary', text: true, size: 'small' },
        accept: () =>
            router.post(route('contas.inativar', parametros(conta)), {}, { preserveScroll: true }),
    });
};

const reativar = (conta: ContaBancaria) => {
    router.post(route('contas.reativar', parametros(conta)), {}, { preserveScroll: true });
};
</script>

<template>
    <div class="rounded-lg border border-ink-8 bg-white p-5 shadow-card">
        <div class="mb-3.5 flex items-center justify-between gap-2">
            <h2 class="text-[12px] font-semibold uppercase tracking-[0.06em] text-ink-55">
                Contas e chaves Pix
            </h2>
            <button
                v-if="pode('contas.gerenciar')"
                type="button"
                class="rounded-lg p-1.5 text-ink-70 hover:bg-ink-8"
                title="Adicionar conta"
                aria-label="Adicionar conta"
                @click="abrirDialog"
            >
                <Icone nome="plus" :tamanho="15" />
            </button>
        </div>

        <!-- Estado vazio: diz a consequência, não só que a lista está vazia. -->
        <div v-if="!contas.length" class="px-2 py-6 text-center">
            <span
                class="mx-auto mb-2.5 flex h-11 w-11 items-center justify-center rounded-full bg-ink-8 text-ink-55"
            >
                <Icone nome="bank" :tamanho="22" />
            </span>
            <p class="text-[13px] font-semibold">Nenhuma conta cadastrada</p>
            <p class="mx-auto mt-1 max-w-[240px] text-[12px] text-ink-55">
                Sem conta principal, o lançamento fica sem destino padrão.
            </p>
            <Button
                v-if="pode('contas.gerenciar')"
                label="Cadastrar conta"
                size="small"
                class="mt-3.5"
                @click="abrirDialog"
            >
                <template #icon><Icone nome="plus" :tamanho="14" /></template>
            </Button>
        </div>

        <div
            v-for="conta in contas"
            :key="conta.id"
            class="border-t border-ink-8 py-3.5 first:border-t-0 first:pt-0"
            :class="conta.status === 'inativa' ? 'opacity-70' : ''"
        >
            <div class="flex items-start justify-between gap-2.5">
                <div class="min-w-0">
                    <p class="truncate text-[13.75px] font-semibold">{{ conta.banco }}</p>
                    <p class="mono mt-0.5 text-[12px] text-ink-55">
                        {{ numeroDaConta(conta) }}
                    </p>
                </div>

                <span
                    v-if="conta.status === 'inativa'"
                    class="shrink-0 rounded-full bg-neutro-bg px-2 py-0.5 text-[11px] font-semibold text-neutro"
                >
                    Inativa
                </span>
                <span
                    v-else-if="conta.principal"
                    class="shrink-0 rounded-full bg-laranja-50 px-2 py-0.5 text-[11px] font-semibold text-laranja-700"
                >
                    Principal
                </span>
            </div>

            <p class="mt-1 truncate text-[12px] text-ink-55">
                {{ conta.titular_nome }} ·
                <span class="mono">{{ formatarDocumento(conta.titular_documento) }}</span>
            </p>

            <p
                v-if="conta.chave_pix"
                class="mt-1.5 flex items-center gap-1 truncate text-[12px] text-azul-600"
            >
                <Icone nome="zap" :tamanho="13" class="shrink-0" />
                <span class="mono truncate">{{ conta.chave_pix }}</span>
            </p>

            <div v-if="pode('contas.gerenciar')" class="mt-2.5 flex flex-wrap gap-3">
                <button
                    v-if="conta.status === 'ativa' && !conta.principal"
                    type="button"
                    class="text-[12.25px] font-semibold text-azul-600 hover:underline"
                    @click="definirPrincipal(conta)"
                >
                    Tornar principal
                </button>

                <button
                    v-if="conta.status === 'ativa'"
                    type="button"
                    class="text-[12.25px] font-semibold"
                    :class="
                        bloqueiaInativar(conta)
                            ? 'cursor-not-allowed text-ink-35'
                            : 'text-perigo hover:underline'
                    "
                    :disabled="bloqueiaInativar(conta)"
                    :title="
                        bloqueiaInativar(conta)
                            ? 'Defina outra conta como principal antes de inativar esta'
                            : undefined
                    "
                    @click="inativar(conta)"
                >
                    Inativar
                </button>

                <button
                    v-else
                    type="button"
                    class="text-[12.25px] font-semibold text-azul-600 hover:underline"
                    @click="reativar(conta)"
                >
                    Reativar
                </button>
            </div>
        </div>
    </div>

    <Dialog
        v-model:visible="dialogAberto"
        modal
        header="Nova conta bancária"
        class="w-full max-w-lg"
    >
        <form class="space-y-4" @submit.prevent="salvar">
            <div class="grid gap-4 sm:grid-cols-3">
                <div class="sm:col-span-2">
                    <label class="mb-1.5 block text-sm font-medium">Banco</label>
                    <InputText v-model="form.banco" class="w-full" :invalid="!!form.errors.banco" />
                    <Message v-if="form.errors.banco" severity="error" size="small" variant="simple">
                        {{ form.errors.banco }}
                    </Message>
                </div>

                <div>
                    <label class="mb-1.5 block text-sm font-medium">Código</label>
                    <InputText v-model="form.codigo_banco" class="w-full" placeholder="341" />
                </div>

                <div>
                    <label class="mb-1.5 block text-sm font-medium">Agência</label>
                    <InputText v-model="form.agencia" class="w-full" :invalid="!!form.errors.agencia" />
                    <Message v-if="form.errors.agencia" severity="error" size="small" variant="simple">
                        {{ form.errors.agencia }}
                    </Message>
                </div>

                <div>
                    <label class="mb-1.5 block text-sm font-medium">Conta</label>
                    <InputText v-model="form.conta" class="w-full" :invalid="!!form.errors.conta" />
                    <Message v-if="form.errors.conta" severity="error" size="small" variant="simple">
                        {{ form.errors.conta }}
                    </Message>
                </div>

                <div>
                    <label class="mb-1.5 block text-sm font-medium">Dígito</label>
                    <InputText v-model="form.digito" class="w-full" />
                </div>

                <div class="sm:col-span-3">
                    <label class="mb-1.5 block text-sm font-medium">Tipo de conta</label>
                    <Select
                        v-model="form.tipo_conta"
                        :options="opcoes.tipoConta"
                        option-label="label"
                        option-value="value"
                        class="w-full"
                    />
                </div>

                <div class="sm:col-span-2">
                    <label class="mb-1.5 block text-sm font-medium">Nome do titular</label>
                    <InputText
                        v-model="form.titular_nome"
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

                <div>
                    <label class="mb-1.5 block text-sm font-medium">CPF/CNPJ</label>
                    <InputText
                        v-model="form.titular_documento"
                        class="w-full"
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

                <div>
                    <label class="mb-1.5 block text-sm font-medium">Tipo da chave</label>
                    <Select
                        v-model="form.tipo_chave_pix"
                        :options="opcoes.tipoChavePix"
                        option-label="label"
                        option-value="value"
                        placeholder="Sem Pix"
                        show-clear
                        class="w-full"
                    />
                </div>

                <div class="sm:col-span-2">
                    <label class="mb-1.5 block text-sm font-medium">Chave Pix</label>
                    <InputText
                        v-model="form.chave_pix"
                        class="w-full"
                        :invalid="!!form.errors.chave_pix"
                    />
                    <Message
                        v-if="form.errors.chave_pix"
                        severity="error"
                        size="small"
                        variant="simple"
                    >
                        {{ form.errors.chave_pix }}
                    </Message>
                </div>
            </div>

            <div class="flex items-center gap-2">
                <Checkbox v-model="form.principal" input-id="principal" binary />
                <label for="principal" class="text-sm">
                    Definir como conta principal (destino padrão)
                </label>
            </div>

            <div class="flex justify-end gap-2 pt-2">
                <Button
                    label="Cancelar"
                    severity="secondary"
                    text
                    size="small"
                    @click="dialogAberto = false"
                />
                <Button type="submit" label="Salvar" :loading="form.processing" size="small" />
            </div>
        </form>
    </Dialog>
</template>
