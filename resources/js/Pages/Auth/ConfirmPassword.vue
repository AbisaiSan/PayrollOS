<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import Password from 'primevue/password';
import Button from 'primevue/button';
import Message from 'primevue/message';
import GuestLayout from '@/Layouts/GuestLayout.vue';

const form = useForm({
    password: '',
});

const submit = () => {
    form.post(route('password.confirm'), {
        onFinish: () => form.reset(),
    });
};
</script>

<template>
    <Head title="Confirmar senha" />

    <GuestLayout
        titulo="Confirmar senha"
        descricao="Esta é uma área protegida. Confirme sua senha antes de continuar."
    >
        <form class="space-y-3.5" @submit.prevent="submit">
            <div class="flex flex-col gap-1.5">
                <label for="password" class="text-[12.75px] font-semibold text-ink-90">Senha</label>
                <Password
                    v-model="form.password"
                    input-id="password"
                    :feedback="false"
                    toggle-mask
                    fluid
                    required
                    autofocus
                    autocomplete="current-password"
                    :invalid="!!form.errors.password"
                />
                <Message v-if="form.errors.password" severity="error" size="small" variant="simple">
                    {{ form.errors.password }}
                </Message>
            </div>

            <Button
                type="submit"
                label="Confirmar"
                fluid
                class="!mt-5"
                :loading="form.processing"
            />
        </form>
    </GuestLayout>
</template>
