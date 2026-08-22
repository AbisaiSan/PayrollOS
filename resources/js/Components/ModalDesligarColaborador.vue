<script setup lang="ts">
import { computed, watch } from 'vue';
import { useForm } from '@inertiajs/vue3';
import Dialog from 'primevue/dialog';
import DatePicker from 'primevue/datepicker';
import Textarea from 'primevue/textarea';
import Button from 'primevue/button';
import Message from 'primevue/message';
import Aviso from '@/Components/Aviso.vue';
import { useFormato } from '@/Composables/useFormato';
import type { Colaborador } from '@/types';

/**
 * Desligamento de colaborador (regra 3.1).
 *
 * Não apaga nada: o status vira Desligado e a data entra no cadastro, o que
 * bloqueia novos lançamentos de folha daí em diante. O histórico já lançado
 * continua onde estava.
 */
const props = defineProps<{
    visivel: boolean;
    colaborador: Colaborador;
}>();

const emit = defineEmits<{ 'update:visivel': [valor: boolean] }>();

const { paraDate, paraIso, formatarData } = useFormato();

const hoje = new Date();

const form = useForm({
    data_desligamento: hoje as Date | null,
    observacoes: '',
});

watch(
    () => props.visivel,
    (aberto) => {
        if (aberto) {
            form.reset();
            form.clearErrors();
            form.data_desligamento = hoje;
        }
    },
);

/** O backend recusa data anterior à admissão; o seletor nem a oferece. */
const admissao = computed(() => paraDate(props.colaborador.data_admissao));

const fechar = () => emit('update:visivel', false);

const confirmar = () => {
    form
        .transform((dados) => ({
            data_desligamento: paraIso(dados.data_desligamento),
            observacoes: dados.observacoes || undefined,
        }))
        .post(route('colaboradores.desligar', props.colaborador.id), {
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
                <h3 class="text-[15.5px] font-semibold">Desligar colaborador</h3>
                <p class="mt-[3px] truncate text-[12.25px] text-ink-55">
                    {{ colaborador.nome }} · {{ colaborador.cargo }}
                </p>
            </div>
        </template>

        <form id="form-desligar-colaborador" class="space-y-3.5" @submit.prevent="confirmar">
            <div class="flex flex-col gap-1.5">
                <label for="data-desligamento" class="text-[12.75px] font-semibold text-ink-90">
                    Data de desligamento <span class="text-laranja-600">●</span>
                </label>
                <DatePicker
                    id="data-desligamento"
                    v-model="form.data_desligamento"
                    date-format="dd/mm/yy"
                    :min-date="admissao ?? undefined"
                    show-icon
                    icon-display="input"
                    class="w-full"
                    :invalid="!!form.errors.data_desligamento"
                />
                <span class="text-[11.5px] text-ink-55">
                    Não pode ser anterior à admissão ({{
                        formatarData(colaborador.data_admissao)
                    }})
                </span>
                <Message
                    v-if="form.errors.data_desligamento"
                    severity="error"
                    size="small"
                    variant="simple"
                >
                    {{ form.errors.data_desligamento }}
                </Message>
            </div>

            <div class="flex flex-col gap-1.5">
                <label for="observacoes-desligamento" class="text-[12.75px] font-semibold text-ink-90">
                    Observações
                </label>
                <Textarea
                    id="observacoes-desligamento"
                    v-model="form.observacoes"
                    rows="3"
                    maxlength="5000"
                    class="w-full"
                    placeholder="Opcional — motivo, aviso prévio, o que ficou combinado"
                    :invalid="!!form.errors.observacoes"
                />
                <!--
                    O backend grava este texto no campo observacoes do cadastro, que é o
                    mesmo do formulário. Preencher aqui substitui o que estivesse lá;
                    deixar em branco preserva. Dizer isso evita apagar sem querer.
                -->
                <span v-if="colaborador.observacoes" class="text-[11.5px] text-atencao">
                    O cadastro já tem observações. O que for escrito aqui substitui o texto
                    atual; deixe em branco para mantê-lo.
                </span>
                <Message
                    v-if="form.errors.observacoes"
                    severity="error"
                    size="small"
                    variant="simple"
                >
                    {{ form.errors.observacoes }}
                </Message>
            </div>

            <Aviso tom="atencao" icone="alertTriangle">
                Após confirmar, novos lançamentos de folha ficam bloqueados para este
                colaborador. O histórico de pagamentos é mantido. Rescisão, se houver, deve
                ser lançada como pagamento avulso.
            </Aviso>
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
                form="form-desligar-colaborador"
                label="Confirmar desligamento"
                severity="danger"
                size="small"
                :loading="form.processing"
                :disabled="!form.data_desligamento"
            />
        </template>
    </Dialog>
</template>
