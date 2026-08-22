<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import Password from 'primevue/password';
import Button from 'primevue/button';
import Message from 'primevue/message';
import Icone from '@/Components/Icone.vue';

const form = useForm({
    current_password: '',
    password: '',
    password_confirmation: '',
});

const updatePassword = () => {
    form.put(route('password.update'), {
        preserveScroll: true,
        onSuccess: () => form.reset(),
        onError: () => {
            if (form.errors.password) {
                form.reset('password', 'password_confirmation');
            }

            if (form.errors.current_password) {
                form.reset('current_password');
            }
        },
    });
};
</script>

<template>
    <section>
        <h2 class="text-[14px] font-semibold">Alterar senha</h2>
        <p class="mb-4 mt-1 text-[12px] text-ink-55">
            Use uma senha longa e que você não repita em outro serviço.
        </p>

        <form class="grid gap-4 sm:grid-cols-2" @submit.prevent="updatePassword">
            <div class="flex flex-col gap-1.5 sm:col-span-2">
                <label for="current_password" class="text-[12.75px] font-semibold text-ink-90">
                    Senha atual <span class="text-laranja-600">●</span>
                </label>
                <Password
                    v-model="form.current_password"
                    input-id="current_password"
                    :feedback="false"
                    toggle-mask
                    fluid
                    autocomplete="current-password"
                    :invalid="!!form.errors.current_password"
                />
                <Message
                    v-if="form.errors.current_password"
                    severity="error"
                    size="small"
                    variant="simple"
                >
                    {{ form.errors.current_password }}
                </Message>
            </div>

            <div class="flex flex-col gap-1.5">
                <label for="password" class="text-[12.75px] font-semibold text-ink-90">
                    Nova senha <span class="text-laranja-600">●</span>
                </label>
                <Password
                    v-model="form.password"
                    input-id="password"
                    toggle-mask
                    fluid
                    autocomplete="new-password"
                    :invalid="!!form.errors.password"
                />
                <Message v-if="form.errors.password" severity="error" size="small" variant="simple">
                    {{ form.errors.password }}
                </Message>
            </div>

            <div class="flex flex-col gap-1.5">
                <label for="password_confirmation" class="text-[12.75px] font-semibold text-ink-90">
                    Confirmar nova senha <span class="text-laranja-600">●</span>
                </label>
                <Password
                    v-model="form.password_confirmation"
                    input-id="password_confirmation"
                    :feedback="false"
                    toggle-mask
                    fluid
                    autocomplete="new-password"
                    :invalid="!!form.errors.password_confirmation"
                />
                <Message
                    v-if="form.errors.password_confirmation"
                    severity="error"
                    size="small"
                    variant="simple"
                >
                    {{ form.errors.password_confirmation }}
                </Message>
            </div>

            <div class="flex items-center gap-3 sm:col-span-2">
                <Button
                    type="submit"
                    label="Atualizar senha"
                    size="small"
                    :loading="form.processing"
                >
                    <template #icon><Icone nome="lock" :tamanho="16" /></template>
                </Button>

                <Transition
                    enter-active-class="transition ease-in-out"
                    enter-from-class="opacity-0"
                    leave-active-class="transition ease-in-out"
                    leave-to-class="opacity-0"
                >
                    <p v-if="form.recentlySuccessful" class="text-[12.5px] text-sucesso">
                        Senha atualizada.
                    </p>
                </Transition>
            </div>
        </form>
    </section>
</template>
