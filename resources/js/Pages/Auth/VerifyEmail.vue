<script setup lang="ts">
import { computed } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import Button from 'primevue/button';
import Message from 'primevue/message';
import GuestLayout from '@/Layouts/GuestLayout.vue';

const props = defineProps<{
    status?: string;
}>();

const form = useForm({});

const submit = () => {
    form.post(route('verification.send'));
};

const enviado = computed(() => props.status === 'verification-link-sent');
</script>

<template>
    <Head title="Verificar e-mail" />

    <GuestLayout
        titulo="Verifique seu e-mail"
        descricao="Enviamos um link de verificação para o seu endereço de e-mail. Confira sua caixa de entrada."
    >
        <Message v-if="enviado" severity="success" size="small" class="mb-4">
            Um novo link de verificação foi enviado para o e-mail da sua conta.
        </Message>

        <form @submit.prevent="submit">
            <Button
                type="submit"
                label="Reenviar e-mail de verificação"
                fluid
                :loading="form.processing"
            />
        </form>

        <template #rodape>
            <Link
                :href="route('logout')"
                method="post"
                as="button"
                class="text-[12px] font-semibold text-azul-600 hover:underline"
            >
                Sair
            </Link>
        </template>
    </GuestLayout>
</template>
