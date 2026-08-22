<script setup lang="ts">
import { computed, watch } from 'vue';
import { useForm } from '@inertiajs/vue3';
import Dialog from 'primevue/dialog';
import InputText from 'primevue/inputtext';
import Select from 'primevue/select';
import Textarea from 'primevue/textarea';
import Checkbox from 'primevue/checkbox';
import Button from 'primevue/button';
import Message from 'primevue/message';
import Aviso from '@/Components/Aviso.vue';
import Icone from '@/Components/Icone.vue';
import type { CategoriaPagamento, Opcao } from '@/types';

/**
 * Categoria de pagamento (regra 3.5). Cabe num diálogo: são quatro campos, e
 * tirar quem edita da listagem custaria mais do que a edição em si.
 */
const props = defineProps<{
    visivel: boolean;
    categoria: (CategoriaPagamento & { pagamentos_count?: number }) | null;
    opcoes: { tipo: Opcao[] };
}>();

const emit = defineEmits<{ 'update:visivel': [valor: boolean] }>();

const editando = computed(() => props.categoria !== null);

const form = useForm({
    nome: '',
    tipo: 'outro',
    descricao: '',
    ativo: true,
});

watch(
    () => props.visivel,
    (aberto) => {
        if (!aberto) return;

        form.clearErrors();

        if (!props.categoria) {
            form.reset();

            return;
        }

        form.nome = props.categoria.nome;
        form.tipo = props.categoria.tipo;
        form.descricao = props.categoria.descricao ?? '';
        form.ativo = props.categoria.ativo;
    },
);

/**
 * Categoria já usada não pode ser excluída — desativá-la é o caminho, e é isso
 * que tira ela dos formulários sem mexer no que já foi classificado.
 */
const emUso = computed(() => (props.categoria?.pagamentos_count ?? 0) > 0);
const vaiDesativar = computed(() => editando.value && !form.ativo && props.categoria?.ativo);

const fechar = () => emit('update:visivel', false);

const salvar = () => {
    const opcoes = { preserveScroll: true, onSuccess: () => fechar() };

    if (props.categoria) {
        form.put(route('categorias.update', props.categoria.id), opcoes);

        return;
    }

    form.post(route('categorias.store'), opcoes);
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
                <h3 class="text-[15.5px] font-semibold">
                    {{ editando ? 'Editar categoria' : 'Nova categoria' }}
                </h3>
                <p class="mt-[3px] text-[12.25px] text-ink-55">
                    Classificação usada em pagamentos e relatórios
                </p>
            </div>
        </template>

        <form id="form-categoria" class="space-y-3.5" @submit.prevent="salvar">
            <div class="flex flex-col gap-1.5">
                <label for="categoria-nome" class="text-[12.75px] font-semibold text-ink-90">
                    Nome <span class="text-laranja-600">●</span>
                </label>
                <InputText
                    id="categoria-nome"
                    v-model="form.nome"
                    maxlength="255"
                    placeholder="Ex.: Manutenção predial"
                    class="w-full"
                    :invalid="!!form.errors.nome"
                />
                <span class="text-[11.5px] text-ink-55">Não pode repetir uma categoria existente</span>
                <Message v-if="form.errors.nome" severity="error" size="small" variant="simple">
                    {{ form.errors.nome }}
                </Message>
            </div>

            <div class="flex flex-col gap-1.5">
                <label for="categoria-tipo" class="text-[12.75px] font-semibold text-ink-90">
                    Tipo <span class="text-laranja-600">●</span>
                </label>
                <Select
                    id="categoria-tipo"
                    v-model="form.tipo"
                    :options="opcoes.tipo"
                    option-label="label"
                    option-value="value"
                    class="w-full"
                    :invalid="!!form.errors.tipo"
                />
                <Message v-if="form.errors.tipo" severity="error" size="small" variant="simple">
                    {{ form.errors.tipo }}
                </Message>
            </div>

            <div class="flex flex-col gap-1.5">
                <label for="categoria-descricao" class="text-[12.75px] font-semibold text-ink-90">
                    Descrição
                </label>
                <Textarea
                    id="categoria-descricao"
                    v-model="form.descricao"
                    rows="2"
                    maxlength="255"
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

            <label class="flex items-center gap-2 text-[13px]">
                <Checkbox v-model="form.ativo" input-id="categoria-ativo" binary />
                <span>Ativa</span>
            </label>

            <Aviso v-if="vaiDesativar" tom="atencao" icone="alertTriangle">
                Desativar tira a categoria dos formulários de lançamento.
                <template v-if="emUso">
                    Os {{ categoria?.pagamentos_count }} pagamentos já classificados com ela
                    continuam como estão, e seguem aparecendo nos relatórios.
                </template>
                <template v-else>
                    Nada já lançado muda.
                </template>
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
                form="form-categoria"
                :label="editando ? 'Salvar alterações' : 'Salvar categoria'"
                size="small"
                :loading="form.processing"
            >
                <template #icon><Icone nome="checkCircle" :tamanho="16" /></template>
            </Button>
        </template>
    </Dialog>
</template>
