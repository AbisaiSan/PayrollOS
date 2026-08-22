<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import InputText from 'primevue/inputtext';
import Password from 'primevue/password';
import Button from 'primevue/button';
import Message from 'primevue/message';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import Icone from '@/Components/Icone.vue';

const props = defineProps<{
    email: string;
    token: string;
}>();

const form = useForm({
    token: props.token,
    email: props.email,
    password: '',
    password_confirmation: '',
});

const submit = () => {
    form.post(route('password.store'), {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};
</script>

<template>
    <Head title="Redefinir senha" />

    <GuestLayout titulo="Redefinir senha" descricao="Escolha uma nova senha para sua conta.">
        <form class="space-y-3.5" @submit.prevent="submit">
            <div class="flex flex-col gap-1.5">
                <label for="email" class="text-[12.75px] font-semibold text-ink-90">E-mail</label>
                <span class="relative">
                    <Icone
                        nome="mail"
                        :tamanho="15"
                        class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-ink-35"
                    />
                    <InputText
                        id="email"
                        v-model="form.email"
                        type="email"
                        class="w-full !pl-9"
                        required
                        autocomplete="username"
                        :invalid="!!form.errors.email"
                    />
                </span>
                <Message v-if="form.errors.email" severity="error" size="small" variant="simple">
                    {{ form.errors.email }}
                </Message>
            </div>

            <div class="flex flex-col gap-1.5">
                <label for="password" class="text-[12.75px] font-semibold text-ink-90">
                    Nova senha
                </label>
                <Password
                    v-model="form.password"
                    input-id="password"
                    toggle-mask
                    fluid
                    required
                    autofocus
                    autocomplete="new-password"
                    :invalid="!!form.errors.password"
                />
                <Message v-if="form.errors.password" severity="error" size="small" variant="simple">
                    {{ form.errors.password }}
                </Message>
            </div>

            <div class="flex flex-col gap-1.5">
                <label for="password_confirmation" class="text-[12.75px] font-semibold text-ink-90">
                    Confirmar nova senha
                </label>
                <Password
                    v-model="form.password_confirmation"
                    input-id="password_confirmation"
                    :feedback="false"
                    toggle-mask
                    fluid
                    required
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

            <Button
                type="submit"
                label="Redefinir senha"
                fluid
                class="!mt-5"
                :loading="form.processing"
            />
        </form>
    </GuestLayout>
</template>
