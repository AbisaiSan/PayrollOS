<script setup lang="ts">
import { ref, watch } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import { useToast } from 'primevue/usetoast';
import Toast from 'primevue/toast';
import ConfirmDialog from 'primevue/confirmdialog';
import Menu from 'primevue/menu';
import Icone from '@/Components/Icone.vue';
import LogoCorebanx from '@/Components/LogoCorebanx.vue';
import type { PageProps } from '@/types';

const page = usePage<PageProps>();
const toast = useToast();

const drawerAberto = ref(false);
const menuUsuario = ref();

/**
 * O menu vem pronto do backend (App\Support\Navegacao), já filtrado pelas
 * permissões do usuário. Nenhum slug de permissão é repetido aqui.
 */
const itensUsuario = [
    {
        label: 'Meu perfil',
        icon: 'pi pi-user',
        command: () => router.get(route('profile.edit')),
    },
    {
        label: 'Sair',
        icon: 'pi pi-sign-out',
        command: () => router.post(route('logout')),
    },
];

const ehRotaAtiva = (rota: string) => {
    // "colaboradores.index" acende também em create, edit e show.
    const base = rota.replace(/\.index$/, '');

    return route().current(rota) || route().current(`${base}.*`);
};

const iniciais = (nome: string) =>
    nome
        .split(' ')
        .filter(Boolean)
        .slice(0, 2)
        .map((parte) => parte.charAt(0).toUpperCase())
        .join('');

// Fecha o drawer ao navegar, senão ele fica aberto por cima da tela nova.
router.on('navigate', () => {
    drawerAberto.value = false;
});

// Mensagens vindas de redirect (with('sucesso', ...)) viram toast.
watch(
    () => page.props.flash,
    (flash) => {
        if (flash?.sucesso) {
            toast.add({
                severity: 'success',
                summary: 'Pronto',
                detail: flash.sucesso,
                life: 4000,
            });
        }

        if (flash?.erro) {
            toast.add({
                severity: 'error',
                summary: 'Não foi possível concluir',
                detail: flash.erro,
                life: 6000,
            });
        }
    },
    { immediate: true, deep: true },
);
</script>

<template>
    <div class="min-h-screen bg-app-bg">
        <Toast position="top-right" />
        <ConfirmDialog />

        <!-- Sidebar: fixa no desktop, drawer sobreposto abaixo de 1024px -->
        <aside
            class="fixed inset-y-0 left-0 z-40 flex w-sidebar flex-col gap-1 bg-gradient-to-b from-azul-600 to-azul-700 px-3.5 py-5 text-white transition-transform duration-200 lg:translate-x-0"
            :class="drawerAberto ? 'translate-x-0 shadow-pop' : '-translate-x-full'"
        >
            <Link :href="route('dashboard')" class="flex items-center gap-2.5 px-2 pb-[22px] pt-1.5">
                <LogoCorebanx />
            </Link>

            <nav class="flex flex-1 flex-col overflow-y-auto">
                <template v-for="grupo in page.props.navegacao ?? []" :key="grupo.titulo">
                    <p
                        class="px-2.5 pb-1.5 pt-3.5 text-[10.5px] uppercase tracking-[0.07em] text-white/40"
                    >
                        {{ grupo.titulo }}
                    </p>

                    <Link
                        v-for="item in grupo.itens"
                        :key="item.rota"
                        :href="route(item.rota)"
                        class="flex w-full items-center gap-[11px] rounded-lg px-2.5 py-[9px] text-[13.75px] font-medium transition-colors"
                        :class="
                            ehRotaAtiva(item.rota)
                                ? 'bg-laranja-500 text-white'
                                : 'text-white/70 hover:bg-white/10 hover:text-white'
                        "
                    >
                        <Icone :nome="item.icone" :tamanho="17" class="shrink-0 opacity-85" />
                        {{ item.rotulo }}
                    </Link>
                </template>
            </nav>

            <p
                class="border-t border-white/15 px-2.5 pb-1 pt-3.5 text-[10.75px] leading-[1.5] text-white/50"
            >
                Sistema de controle. Não executa pagamentos.
            </p>
        </aside>

        <!-- Scrim do drawer no mobile -->
        <div
            v-if="drawerAberto"
            class="fixed inset-0 z-30 bg-ink/45 lg:hidden"
            aria-hidden="true"
            @click="drawerAberto = false"
        />

        <div class="lg:pl-sidebar">
            <header
                class="sticky top-0 z-20 flex h-topbar items-center gap-3.5 border-b border-ink-8 bg-white px-3.5 sm:px-6"
            >
                <button
                    type="button"
                    class="rounded-[7px] p-1.5 text-ink-70 hover:bg-ink-8 lg:hidden"
                    aria-label="Abrir menu"
                    @click="drawerAberto = !drawerAberto"
                >
                    <Icone nome="menu" :tamanho="20" />
                </button>

                <div class="min-w-0 flex-1">
                    <slot name="header" />
                </div>

                <div class="ml-auto flex shrink-0 items-center gap-2.5">
                    <button
                        type="button"
                        class="flex items-center gap-2.5 rounded-lg py-1 pl-3 pr-1.5 hover:bg-ink-8 sm:border-l sm:border-ink-8"
                        aria-haspopup="true"
                        @click="menuUsuario.toggle($event)"
                    >
                        <span
                            class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-azul-600 text-[13px] font-semibold text-white"
                        >
                            {{ iniciais(page.props.auth.user.name) }}
                        </span>

                        <span class="hidden text-left leading-[1.25] sm:block">
                            <span class="block text-[12.75px] font-semibold">
                                {{ page.props.auth.user.name }}
                            </span>
                            <span
                                v-if="page.props.auth.perfilRotulo"
                                class="block text-[11px] text-ink-55"
                            >
                                {{ page.props.auth.perfilRotulo }}
                            </span>
                        </span>

                        <Icone nome="chevronDown" :tamanho="15" class="text-ink-55" />
                    </button>

                    <Menu ref="menuUsuario" :model="itensUsuario" :popup="true" />
                </div>
            </header>

            <main class="p-4 sm:p-6">
                <slot />
            </main>
        </div>
    </div>
</template>
