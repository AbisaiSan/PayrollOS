<script setup lang="ts">
import { ref, watch } from 'vue';
import { useForm } from '@inertiajs/vue3';
import Dialog from 'primevue/dialog';
import Textarea from 'primevue/textarea';
import DatePicker from 'primevue/datepicker';
import Button from 'primevue/button';
import Message from 'primevue/message';
import StatusBadge from '@/Components/StatusBadge.vue';
import Aviso from '@/Components/Aviso.vue';
import { useFormato } from '@/Composables/useFormato';
import type { Opcao } from '@/types';

/**
 * Mudança manual de status.
 *
 * As opções vêm exclusivamente de transicoesPermitidas, calculado pelo backend a
 * partir do status atual. A tabela de transições do briefing é referência de
 * leitura, não fonte de verdade: replicá-la aqui deixaria a tela oferecendo
 * caminhos que o service recusaria.
 */
const props = defineProps<{
    visivel: boolean;
    pagamento: { id: number; descricao: string; status: string };
    transicoesPermitidas: Opcao[];
}>();

const emit = defineEmits<{ 'update:visivel': [valor: boolean] }>();

const { paraIso } = useFormato();

const hoje = new Date();

const form = useForm({
    status: '',
    observacao: '',
    data_pagamento: null as Date | null,
});

watch(
    () => props.visivel,
    (aberto) => {
        if (aberto) {
            form.reset();
            form.clearErrors();
            // Sem opção pré-selecionada: escolher o destino é uma decisão consciente.
            form.status = '';
        }
    },
);

const fechar = () => emit('update:visivel', false);

const salvar = () => {
    form
        .transform((dados) => ({
            status: dados.status,
            observacao: dados.observacao || undefined,
            // Só faz sentido quando o destino é "pago"; nos demais o backend ignora.
            data_pagamento: dados.status === 'pago' ? paraIso(dados.data_pagamento) : undefined,
        }))
        .post(route('pagamentos.status', props.pagamento.id), {
            preserveScroll: true,
            onSuccess: () => fechar(),
        });
};
</script>

<template>
    <Dialog
        :visible="visivel"
        modal
        :draggable="false"
        class="w-full max-w-[460px]"
        @update:visible="emit('update:visivel', $event)"
    >
        <template #header>
            <div class="min-w-0">
                <h3 class="text-[15.5px] font-semibold">Mudar status</h3>
                <p class="mt-[3px] flex items-center gap-1.5 truncate text-[12.25px] text-ink-55">
                    <span class="truncate">{{ pagamento.descricao }}</span>
                    <span>· atual:</span>
                    <StatusBadge :status="pagamento.status" tamanho="sm" />
                </p>
            </div>
        </template>

        <form id="form-mudar-status" class="space-y-3.5" @submit.prevent="salvar">
            <p class="text-[11.5px] text-ink-55">
                Só são exibidas as transições permitidas para este pagamento.
            </p>

            <div class="flex flex-col gap-2" role="radiogroup" aria-label="Novo status">
                <button
                    v-for="opcao in transicoesPermitidas"
                    :key="opcao.value"
                    type="button"
                    role="radio"
                    :aria-checked="form.status === opcao.value"
                    class="flex items-center gap-2.5 rounded-[9px] border-[1.5px] bg-white px-3 py-2.5 text-left transition-colors"
                    :class="
                        form.status === opcao.value
                            ? 'border-azul-500 bg-azul-50'
                            : 'border-ink-16 hover:border-azul-300 hover:bg-azul-50'
                    "
                    @click="form.status = opcao.value"
                >
                    <span class="flex-1 text-[13.25px] font-semibold">{{ opcao.label }}</span>
                    <StatusBadge :status="opcao.value" tamanho="sm" />
                </button>
            </div>

            <Message v-if="form.errors.status" severity="error" size="small" variant="simple">
                {{ form.errors.status }}
            </Message>

            <!-- Marcar como pago exige a data em que o dinheiro saiu. -->
            <div v-if="form.status === 'pago'" class="flex flex-col gap-1.5">
                <Aviso>
                    Marcar como pago registra um pagamento <strong>já feito por fora</strong>.
                    Nenhuma transferência é disparada por aqui.
                </Aviso>

                <label for="data-pagamento-status" class="text-[12.75px] font-semibold text-ink-90">
                    Data de pagamento
                    <span class="text-laranja-600">●</span>
                </label>
                <DatePicker
                    id="data-pagamento-status"
                    v-model="form.data_pagamento"
                    date-format="dd/mm/yy"
                    :max-date="hoje"
                    show-icon
                    icon-display="input"
                    class="w-full"
                    :invalid="!!form.errors.data_pagamento"
                />
                <Message
                    v-if="form.errors.data_pagamento"
                    severity="error"
                    size="small"
                    variant="simple"
                >
                    {{ form.errors.data_pagamento }}
                </Message>
            </div>

            <div class="flex flex-col gap-1.5">
                <label for="observacao-status" class="text-[12.75px] font-semibold text-ink-90">
                    Observação
                </label>
                <Textarea
                    id="observacao-status"
                    v-model="form.observacao"
                    rows="2"
                    class="w-full"
                    placeholder="Opcional — fica registrado na linha do tempo"
                    :invalid="!!form.errors.observacao"
                />
                <Message v-if="form.errors.observacao" severity="error" size="small" variant="simple">
                    {{ form.errors.observacao }}
                </Message>
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
                form="form-mudar-status"
                label="Salvar mudança"
                size="small"
                :loading="form.processing"
                :disabled="!form.status"
            />
        </template>
    </Dialog>
</template>
