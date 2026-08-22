<script setup lang="ts">
import { computed, ref } from 'vue';
import { router } from '@inertiajs/vue3';
import Button from 'primevue/button';
import { useConfirm } from 'primevue/useconfirm';
import Icone from '@/Components/Icone.vue';
import ModalContaBancaria from '@/Components/ModalContaBancaria.vue';
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
const contaEmEdicao = ref<ContaBancaria | null>(null);

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

const abrirDialog = (conta: ContaBancaria | null = null) => {
    contaEmEdicao.value = conta;
    dialogAberto.value = true;
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
                @click="abrirDialog()"
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
                @click="abrirDialog()"
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
                    type="button"
                    class="text-[12.25px] font-semibold text-azul-600 hover:underline"
                    @click="abrirDialog(conta)"
                >
                    Editar
                </button>

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

    <ModalContaBancaria
        v-model:visivel="dialogAberto"
        :tipo-beneficiario="tipoBeneficiario"
        :beneficiario-id="beneficiarioId"
        :conta="contaEmEdicao"
        :opcoes="opcoes"
        :unica-conta="contas.length <= 1"
    />
</template>
