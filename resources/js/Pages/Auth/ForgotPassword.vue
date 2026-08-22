<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import InputText from 'primevue/inputtext';
import Button from 'primevue/button';
import Message from 'primevue/message';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import Icone from '@/Components/Icone.vue';

defineProps<{
    status?: string;
}>();

const form = useForm({
    email: '',
});

const submit = () => {
    form.post(route('password.email'));
};
</script>

<template>
    <Head title="Esqueci a senha" />

    <GuestLayout
        titulo="Esqueci a senha"
        descricao="Informe seu e-mail cadastrado e enviaremos um link para redefinir a senha."
    >
        <Message v-if="status" severity="success" size="small" class="mb-4">
            {{ status }}
        </Message>

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
                        placeholder="voce@corebanx.com"
                        class="w-full !pl-9"
                        required
                        autofocus
                        autocomplete="username"
                        :invalid="!!form.errors.email"
                    />
                </span>
                <Message v-if="form.errors.email" severity="error" size="small" variant="simple">
                    {{ form.errors.email }}
                </Message>
            </div>

            <Button
                type="submit"
                label="Enviar link de redefinição"
                fluid
                class="!mt-5"
                :loading="form.processing"
            />
        </form>

        <template #rodape>
            <Link :href="route('login')" class="text-[12px] font-semibold text-azul-600 hover:underline">
                Voltar para o login
            </Link>
        </template>
    </GuestLayout>
</template>
