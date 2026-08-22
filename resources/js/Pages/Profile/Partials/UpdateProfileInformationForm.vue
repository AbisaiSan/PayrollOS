<script setup lang="ts">
import { Link, useForm, usePage } from '@inertiajs/vue3';
import InputText from 'primevue/inputtext';
import Button from 'primevue/button';
import Message from 'primevue/message';
import Icone from '@/Components/Icone.vue';
import type { PageProps } from '@/types';

defineProps<{
    mustVerifyEmail?: boolean;
    status?: string;
}>();

const user = usePage<PageProps>().props.auth.user;

const form = useForm({
    name: user.name,
    email: user.email,
});
</script>

<template>
    <section>
        <h2 class="text-[14px] font-semibold">Dados do perfil</h2>
        <p class="mb-4 mt-1 text-[12px] text-ink-55">
            Nome e e-mail usados para entrar no sistema.
        </p>

        <form class="grid gap-4" @submit.prevent="form.patch(route('profile.update'))">
            <div class="flex flex-col gap-1.5">
                <label for="name" class="text-[12.75px] font-semibold text-ink-90">
                    Nome <span class="text-laranja-600">●</span>
                </label>
                <InputText
                    id="name"
                    v-model="form.name"
                    maxlength="255"
                    class="w-full"
                    required
                    autocomplete="name"
                    :invalid="!!form.errors.name"
                />
                <Message v-if="form.errors.name" severity="error" size="small" variant="simple">
                    {{ form.errors.name }}
                </Message>
            </div>

            <div class="flex flex-col gap-1.5">
                <label for="email" class="text-[12.75px] font-semibold text-ink-90">
                    E-mail <span class="text-laranja-600">●</span>
                </label>
                <InputText
                    id="email"
                    v-model="form.email"
                    type="email"
                    maxlength="255"
                    class="w-full"
                    required
                    autocomplete="username"
                    :invalid="!!form.errors.email"
                />
                <Message v-if="form.errors.email" severity="error" size="small" variant="simple">
                    {{ form.errors.email }}
                </Message>
            </div>

            <div v-if="mustVerifyEmail && user.email_verified_at === null">
                <p class="text-[12.5px] text-ink-70">
                    Seu e-mail ainda não foi verificado.
                    <Link
                        :href="route('verification.send')"
                        method="post"
                        as="button"
                        class="font-semibold text-azul-600 hover:underline"
                    >
                        Reenviar o e-mail de verificação
                    </Link>
                </p>

                <Message v-if="status === 'verification-link-sent'" severity="success" size="small" class="mt-2">
                    Um novo link de verificação foi enviado para o seu e-mail.
                </Message>
            </div>

            <div class="flex items-center gap-3">
                <Button type="submit" label="Salvar" size="small" :loading="form.processing">
                    <template #icon><Icone nome="checkCircle" :tamanho="16" /></template>
                </Button>

                <Transition
                    enter-active-class="transition ease-in-out"
                    enter-from-class="opacity-0"
                    leave-active-class="transition ease-in-out"
                    leave-to-class="opacity-0"
                >
                    <p v-if="form.recentlySuccessful" class="text-[12.5px] text-sucesso">
                        Perfil atualizado.
                    </p>
                </Transition>
            </div>
        </form>
    </section>
</template>
