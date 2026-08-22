<script setup lang="ts">
import { computed, watch } from 'vue';
import { useForm } from '@inertiajs/vue3';
import Dialog from 'primevue/dialog';
import Textarea from 'primevue/textarea';
import Button from 'primevue/button';
import Message from 'primevue/message';
import StatusBadge from '@/Components/StatusBadge.vue';
import Aviso from '@/Components/Aviso.vue';

/**
 * Rejeição de reembolso.
 *
 * O motivo é obrigatório porque a rejeição é a única transição em que o
 * solicitante fica sem saber o que fazer a seguir sem uma explicação. Ele vai
 * para o histórico junto com a mudança de status, na mesma linha do tempo que
 * o detalhe mostra — não é um comentário solto.
 *
 * A obrigatoriedade também está no backend: aqui ela só evita a ida ao servidor.
 */
const props = defineProps<{
    visivel: boolean;
    reembolso: { id: number; descricao: string; status: string; colaborador: { nome: string } };
}>();

const emit = defineEmits<{ 'update:visivel': [valor: boolean] }>();

const LIMITE = 1000;

const form = useForm({
    status: 'rejeitado',
    observacao: '',
});

watch(
    () => props.visivel,
    (aberto) => {
        if (aberto) {
            form.reset();
            form.clearErrors();
        }
    },
);

const motivoPreenchido = computed(() => form.observacao.trim().length > 0);

const fechar = () => emit('update:visivel', false);

const rejeitar = () => {
    form.post(route('reembolsos.status', props.reembolso.id), {
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
        class="w-full max-w-[480px]"
        @update:visible="emit('update:visivel', $event)"
    >
        <template #header>
            <div class="min-w-0">
                <h3 class="text-[15.5px] font-semibold">Rejeitar reembolso</h3>
                <p class="mt-[3px] flex items-center gap-1.5 truncate text-[12.25px] text-ink-55">
                    <span class="mono">#{{ reembolso.id }}</span>
                    <span>·</span>
                    <span class="truncate">{{ reembolso.colaborador.nome }}</span>
                    <StatusBadge :status="reembolso.status" tamanho="sm" />
                </p>
            </div>
        </template>

        <form id="form-rejeitar-reembolso" class="space-y-3.5" @submit.prevent="rejeitar">
            <Aviso tom="atencao" icone="alertTriangle">
                A solicitação passa para <strong>Rejeitado</strong> e sai da fila de aprovação.
                Ela pode ser reaberta depois, e o histórico é preservado.
            </Aviso>

            <div class="flex flex-col gap-1.5">
                <label for="motivo-rejeicao" class="text-[12.75px] font-semibold text-ink-90">
                    Motivo da rejeição <span class="text-laranja-600">●</span>
                </label>
                <Textarea
                    id="motivo-rejeicao"
                    v-model="form.observacao"
                    rows="4"
                    :maxlength="LIMITE"
                    class="w-full"
                    placeholder="Ex.: fora da política de viagens vigente — reenviar com a justificativa do gestor"
                    :invalid="!!form.errors.observacao"
                />
                <div class="flex items-start justify-between gap-3">
                    <span class="text-[11.5px] text-ink-55">
                        Vai para a linha do tempo e explica ao solicitante o que fazer a seguir.
                    </span>
                    <span class="mono shrink-0 text-[11.5px] text-ink-35">
                        {{ form.observacao.length }}/{{ LIMITE }}
                    </span>
                </div>
                <Message
                    v-if="form.errors.observacao"
                    severity="error"
                    size="small"
                    variant="simple"
                >
                    {{ form.errors.observacao }}
                </Message>
            </div>

            <Message v-if="form.errors.status" severity="error" size="small" variant="simple">
                {{ form.errors.status }}
            </Message>
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
                form="form-rejeitar-reembolso"
                label="Rejeitar solicitação"
                severity="danger"
                size="small"
                :loading="form.processing"
                :disabled="!motivoPreenchido"
            />
        </template>
    </Dialog>
</template>
