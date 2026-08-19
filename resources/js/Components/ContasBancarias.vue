<script setup lang="ts">
import { ref } from 'vue';
import { router, useForm } from '@inertiajs/vue3';
import Button from 'primevue/button';
import Dialog from 'primevue/dialog';
import InputText from 'primevue/inputtext';
import Select from 'primevue/select';
import Checkbox from 'primevue/checkbox';
import Message from 'primevue/message';
import { useConfirm } from 'primevue/useconfirm';
import StatusBadge from '@/Components/StatusBadge.vue';
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
        message:
            'A conta deixa de aparecer para novos lançamentos, mas continua vinculada aos pagamentos já feitos. Confirmar?',
        header: 'Inativar conta',
        acceptLabel: 'Inativar',
        rejectLabel: 'Cancelar',
        accept: () =>
            router.post(route('contas.inativar', parametros(conta)), {}, { preserveScroll: true }),
    });
};

const reativar = (conta: ContaBancaria) => {
    router.post(route('contas.reativar', parametros(conta)), {}, { preserveScroll: true });
};
</script>

<template>
    <div class="rounded-xl border border-black/5 bg-white">
        <div class="flex items-center justify-between border-b border-black/5 px-5 py-4">
            <h2 class="text-sm font-semibold text-corebanx-preto">
                Contas e chaves Pix
            </h2>
            <Button
                v-if="pode('contas.gerenciar')"
                icon="pi pi-plus"
                size="small"
                text
                rounded
                aria-label="Adicionar conta"
                @click="abrirDialog"
            />
        </div>

        <div class="divide-y divide-black/5">
            <p v-if="!contas.length" class="px-5 py-8 text-center text-sm text-corebanx-preto/45">
                Nenhuma conta cadastrada. Sem conta principal, o lançamento fica sem
                destino padrão.
            </p>

            <div v-for="conta in contas" :key="conta.id" class="px-5 py-4">
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        <p class="truncate text-sm font-medium text-corebanx-preto">
                            {{ conta.banco }}
                        </p>
                        <p class="mt-0.5 text-xs text-corebanx-preto/55">
                            Ag. {{ conta.agencia }} ·
                            {{ conta.tipo_conta === 'poupanca' ? 'Poupança' : 'C/C' }}
                            {{ conta.conta }}{{ conta.digito ? `-${conta.digito}` : '' }}
                        </p>
                        <p class="mt-0.5 truncate text-xs text-corebanx-preto/55">
                            {{ conta.titular_nome }} ·
                            {{ formatarDocumento(conta.titular_documento) }}
                        </p>
                        <p v-if="conta.chave_pix" class="mt-1 truncate text-xs text-corebanx-azul">
                            <i class="pi pi-bolt mr-1 text-[10px]" />
                            {{ conta.chave_pix }}
                        </p>
                    </div>

                    <div class="flex shrink-0 flex-col items-end gap-1.5">
                        <span
                            v-if="conta.principal"
                            class="rounded-full bg-laranja-100 px-2 py-0.5 text-[11px] font-medium text-laranja-700"
                        >
                            Principal
                        </span>
                        <StatusBadge v-else-if="conta.status === 'inativa'" status="inativa" />
                    </div>
                </div>

                <div v-if="pode('contas.gerenciar')" class="mt-3 flex flex-wrap gap-1">
                    <Button
                        v-if="!conta.principal && conta.status === 'ativa'"
                        label="Tornar principal"
                        size="small"
                        text
                        @click="definirPrincipal(conta)"
                    />
                    <Button
                        v-if="conta.status === 'ativa'"
                        label="Inativar"
                        size="small"
                        text
                        severity="secondary"
                        @click="inativar(conta)"
                    />
                    <Button
                        v-else
                        label="Reativar"
                        size="small"
                        text
                        severity="secondary"
                        @click="reativar(conta)"
                    />
                </div>
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
