<script setup lang="ts">
import { ref, watch } from 'vue';
import { useForm } from '@inertiajs/vue3';
import Dialog from 'primevue/dialog';
import DatePicker from 'primevue/datepicker';
import Textarea from 'primevue/textarea';
import Button from 'primevue/button';
import Message from 'primevue/message';
import Aviso from '@/Components/Aviso.vue';
import Icone from '@/Components/Icone.vue';
import { useFormato } from '@/Composables/useFormato';

/**
 * Confirmação manual de que o pagamento saiu.
 *
 * O sistema não executa pagamento nenhum: este diálogo registra algo que já foi
 * feito por fora, pelo internet banking. Por isso o vocabulário é "confirmar" e
 * "registrar como pago" — nunca "pagar", "enviar" ou "processar".
 */
const props = defineProps<{
    visivel: boolean;
    pagamento: { id: number; descricao: string; valor: string };
}>();

const emit = defineEmits<{ 'update:visivel': [valor: boolean] }>();

const { formatarMoeda, paraIso } = useFormato();

const hoje = new Date();

const form = useForm({
    data_pagamento: null as Date | null,
    observacao: '',
});

// Reabrir o diálogo não deve trazer o rascunho anterior nem os erros da tentativa passada.
watch(
    () => props.visivel,
    (aberto) => {
        if (aberto) {
            form.reset();
            form.clearErrors();
            form.data_pagamento = new Date();
        }
    },
);

const fechar = () => emit('update:visivel', false);

const registrar = () => {
    form
        .transform((dados) => ({
            ...dados,
            data_pagamento: paraIso(dados.data_pagamento),
        }))
        .post(route('pagamentos.confirmar', props.pagamento.id), {
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
                <h3 class="text-[15.5px] font-semibold">Confirmar pagamento</h3>
                <p class="mt-[3px] truncate text-[12.25px] text-ink-55">
                    {{ pagamento.descricao }} ·
                    <span class="mono">{{ formatarMoeda(pagamento.valor) }}</span>
                </p>
            </div>
        </template>

        <form id="form-confirmar-pagamento" class="space-y-3.5" @submit.prevent="registrar">
            <Aviso>
                Isto registra que o pagamento <strong>já foi feito por fora</strong>, pelo
                internet banking. Nenhuma transferência é disparada por aqui.
            </Aviso>

            <div class="flex flex-col gap-1.5">
                <label for="data-pagamento" class="text-[12.75px] font-semibold text-ink-90">
                    Data de pagamento
                    <span class="text-laranja-600">●</span>
                </label>
                <DatePicker
                    id="data-pagamento"
                    v-model="form.data_pagamento"
                    date-format="dd/mm/yy"
                    :max-date="hoje"
                    show-icon
                    icon-display="input"
                    class="w-full"
                    :invalid="!!form.errors.data_pagamento"
                />
                <span class="text-[11.5px] text-ink-55">Não pode ser uma data futura</span>
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
                <label for="observacao-pagamento" class="text-[12.75px] font-semibold text-ink-90">
                    Observação
                </label>
                <Textarea
                    id="observacao-pagamento"
                    v-model="form.observacao"
                    rows="3"
                    class="w-full"
                    placeholder="Ex.: comprovante Itaú anexado, pago via Pix"
                    :invalid="!!form.errors.observacao"
                />
                <span class="text-[11.5px] text-ink-55">
                    Fica registrada na linha do tempo do lançamento
                </span>
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
                form="form-confirmar-pagamento"
                label="Registrar como pago"
                size="small"
                :loading="form.processing"
            >
                <template #icon><Icone nome="checkCircle" :tamanho="16" /></template>
            </Button>
        </template>
    </Dialog>
</template>
