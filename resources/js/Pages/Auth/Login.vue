<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import InputText from 'primevue/inputtext';
import Password from 'primevue/password';
import Checkbox from 'primevue/checkbox';
import Button from 'primevue/button';
import Message from 'primevue/message';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import Icone from '@/Components/Icone.vue';

defineProps<{
    canResetPassword?: boolean;
    status?: string;
}>();

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const submit = () => {
    form.post(route('login'), {
        onFinish: () => form.reset('password'),
    });
};
</script>

<template>
    <Head title="Entrar" />

    <GuestLayout
        titulo="Entrar"
        descricao="Acesso restrito. Contas são criadas pelo administrador — não há cadastro público."
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

            <div class="flex flex-col gap-1.5">
                <label for="password" class="text-[12.75px] font-semibold text-ink-90">Senha</label>
                <Password
                    v-model="form.password"
                    input-id="password"
                    :feedback="false"
                    toggle-mask
                    fluid
                    input-class="w-full"
                    required
                    autocomplete="current-password"
                    :invalid="!!form.errors.password"
                />
                <Message v-if="form.errors.password" severity="error" size="small" variant="simple">
                    {{ form.errors.password }}
                </Message>
            </div>

            <div class="flex items-center justify-between gap-3 pt-0.5">
                <label class="flex items-center gap-2 text-[12.5px] text-ink-70">
                    <Checkbox v-model="form.remember" input-id="remember" binary />
                    Lembrar-me
                </label>

                <Link
                    v-if="canResetPassword"
                    :href="route('password.request')"
                    class="text-[12.5px] font-semibold text-azul-600 hover:underline"
                >
                    Esqueceu a senha?
                </Link>
            </div>

            <Button
                type="submit"
                label="Entrar"
                fluid
                class="!mt-5"
                :loading="form.processing"
            />
        </form>

        <!--
            Sem link de cadastro: as rotas de registro foram removidas de propósito.
            Dizer o que o sistema não faz evita a expectativa de que ele pague.
        -->
        <p class="mt-5 border-t border-ink-8 pt-4 text-center text-[11.5px] text-ink-55">
            Sistema de controle interno. Não executa pagamentos.
        </p>
    </GuestLayout>
</template>
