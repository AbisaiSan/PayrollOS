<script setup lang="ts">
import { ref, watch } from 'vue';
import { useForm } from '@inertiajs/vue3';
import Dialog from 'primevue/dialog';
import Password from 'primevue/password';
import Button from 'primevue/button';
import Message from 'primevue/message';
import Aviso from '@/Components/Aviso.vue';
import Icone from '@/Components/Icone.vue';

const dialogAberto = ref(false);

const form = useForm({
    password: '',
});

watch(dialogAberto, (aberto) => {
    if (aberto) {
        form.reset();
        form.clearErrors();
    }
});

const excluir = () => {
    form.delete(route('profile.destroy'), {
        preserveScroll: true,
        onSuccess: () => {
            dialogAberto.value = false;
        },
    });
};
</script>

<template>
    <section>
        <h2 class="text-[14px] font-semibold text-perigo">Excluir conta</h2>
        <p class="mb-4 mt-1 text-[12px] text-ink-55">
            Esta ação é permanente. Todos os dados de acesso serão removidos.
        </p>

        <!--
            Lançamentos e reembolsos não somem junto: eles apontam para o registro,
            e a auditoria guarda quem fez o quê. Dizer isso evita a leitura de que
            excluir a conta apaga o histórico financeiro.
        -->
        <Aviso tom="atencao" icone="alertTriangle" class="mb-4">
            O que já foi lançado permanece: os pagamentos, reembolsos e a trilha de
            auditoria continuam registrados. O que se perde é o acesso.
        </Aviso>

        <Button
            label="Excluir minha conta"
            severity="danger"
            outlined
            size="small"
            @click="dialogAberto = true"
        >
            <template #icon><Icone nome="trash" :tamanho="16" /></template>
        </Button>

        <Dialog
            v-model:visible="dialogAberto"
            modal
            :draggable="false"
            class="w-full max-w-[460px]"
        >
            <template #header>
                <div class="min-w-0">
                    <h3 class="text-[15.5px] font-semibold">Excluir conta</h3>
                    <p class="mt-[3px] text-[12.25px] text-ink-55">
                        Confirme sua senha para continuar
                    </p>
                </div>
            </template>

            <form id="form-excluir-conta" class="space-y-3.5" @submit.prevent="excluir">
                <p class="text-[13px] text-ink-70">
                    Depois de excluída, a conta não pode ser recuperada. Você sairá do sistema
                    imediatamente.
                </p>

                <div class="flex flex-col gap-1.5">
                    <label for="senha-exclusao" class="text-[12.75px] font-semibold text-ink-90">
                        Senha <span class="text-laranja-600">●</span>
                    </label>
                    <Password
                        v-model="form.password"
                        input-id="senha-exclusao"
                        :feedback="false"
                        toggle-mask
                        fluid
                        autocomplete="current-password"
                        :invalid="!!form.errors.password"
                    />
                    <Message
                        v-if="form.errors.password"
                        severity="error"
                        size="small"
                        variant="simple"
                    >
                        {{ form.errors.password }}
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
                    @click="dialogAberto = false"
                />
                <Button
                    type="submit"
                    form="form-excluir-conta"
                    label="Excluir conta"
                    severity="danger"
                    size="small"
                    :loading="form.processing"
                    :disabled="!form.password"
                />
            </template>
        </Dialog>
    </section>
</template>
