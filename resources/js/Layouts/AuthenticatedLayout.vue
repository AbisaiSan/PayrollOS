<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import { useToast } from 'primevue/usetoast';
import Toast from 'primevue/toast';
import ConfirmDialog from 'primevue/confirmdialog';
import Menu from 'primevue/menu';
import LogoCorebanx from '@/Components/LogoCorebanx.vue';
import { usePermissoes } from '@/Composables/usePermissoes';
import type { PageProps } from '@/types';

const page = usePage<PageProps>();
const toast = useToast();
const { pode } = usePermissoes();

const menuAberto = ref(false);
const menuUsuario = ref();

interface ItemMenu {
    rotulo: string;
    rota: string;
    icone: string;
    permissao?: string;
}

/**
 * Ordem pensada para o uso diario do financeiro: o que se lanca todo dia vem
 * antes do que se cadastra uma vez.
 */
const itens: ItemMenu[] = [
    { rotulo: 'Dashboard', rota: 'dashboard', icone: 'pi pi-home' },
    {
        rotulo: 'Pagamentos',
        rota: 'pagamentos.index',
        icone: 'pi pi-wallet',
        permissao: 'pagamentos.ver',
    },
    {
        rotulo: 'Reembolsos',
        rota: 'reembolsos.index',
        icone: 'pi pi-receipt',
        permissao: 'reembolsos.ver',
    },
    {
        rotulo: 'Colaboradores',
        rota: 'colaboradores.index',
        icone: 'pi pi-users',
        permissao: 'colaboradores.ver',
    },
    {
        rotulo: 'Fornecedores',
        rota: 'fornecedores.index',
        icone: 'pi pi-briefcase',
        permissao: 'fornecedores.ver',
    },
    {
        rotulo: 'Contratos',
        rota: 'contratos.index',
        icone: 'pi pi-file',
        permissao: 'contratos.ver',
    },
    {
        rotulo: 'Categorias',
        rota: 'categorias.index',
        icone: 'pi pi-tags',
        permissao: 'categorias.ver',
    },
    {
        rotulo: 'Relatórios',
        rota: 'relatorios.index',
        icone: 'pi pi-chart-bar',
        permissao: 'relatorios.ver',
    },
    {
        rotulo: 'Auditoria',
        rota: 'auditoria.index',
        icone: 'pi pi-history',
        permissao: 'auditoria.ver',
    },
];

const itensVisiveis = computed(() =>
    itens.filter((item) => !item.permissao || pode(item.permissao)),
);

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
    const base = rota.replace(/\.index$/, '');

    return route().current(rota) || route().current(`${base}.*`);
};

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
    <div class="min-h-screen bg-corebanx-cinza">
        <Toast position="top-right" />
        <ConfirmDialog />

        <!-- Sidebar: fixa no desktop, drawer no mobile -->
        <aside
            class="fixed inset-y-0 left-0 z-40 flex w-64 flex-col bg-corebanx-azul transition-transform duration-200 lg:translate-x-0"
            :class="menuAberto ? 'translate-x-0' : '-translate-x-full'"
        >
            <div class="flex h-16 items-center px-5">
                <Link :href="route('dashboard')">
                    <LogoCorebanx />
                </Link>
            </div>

            <nav class="flex-1 space-y-0.5 overflow-y-auto px-3 py-4">
                <Link
                    v-for="item in itensVisiveis"
                    :key="item.rota"
                    :href="route(item.rota)"
                    class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition-colors"
                    :class="
                        ehRotaAtiva(item.rota)
                            ? 'bg-corebanx-laranja text-white'
                            : 'text-white/70 hover:bg-white/10 hover:text-white'
                    "
                    @click="menuAberto = false"
                >
                    <i :class="item.icone" class="text-base" />
                    {{ item.rotulo }}
                </Link>
            </nav>

            <div class="border-t border-white/10 px-5 py-3">
                <p class="text-[11px] leading-relaxed text-white/40">
                    Sistema de controle. Não executa pagamentos.
                </p>
            </div>
        </aside>

        <!-- Fundo escuro do drawer no mobile -->
        <div
            v-if="menuAberto"
            class="fixed inset-0 z-30 bg-corebanx-preto/50 lg:hidden"
            @click="menuAberto = false"
        />

        <div class="lg:pl-64">
            <header
                class="sticky top-0 z-20 flex h-16 items-center gap-4 border-b border-black/5 bg-white px-4 sm:px-6"
            >
                <button
                    type="button"
                    class="rounded-lg p-2 text-corebanx-preto/60 hover:bg-corebanx-cinza lg:hidden"
                    aria-label="Abrir menu"
                    @click="menuAberto = !menuAberto"
                >
                    <i class="pi pi-bars" />
                </button>

                <div class="min-w-0 flex-1">
                    <slot name="header" />
                </div>

                <button
                    type="button"
                    class="flex items-center gap-2.5 rounded-lg px-2 py-1.5 text-sm hover:bg-corebanx-cinza"
                    aria-haspopup="true"
                    @click="menuUsuario.toggle($event)"
                >
                    <span
                        class="flex h-8 w-8 items-center justify-center rounded-full bg-corebanx-azul text-xs font-semibold text-white"
                    >
                        {{ page.props.auth.user.name.charAt(0).toUpperCase() }}
                    </span>
                    <span class="hidden font-medium text-corebanx-preto sm:block">
                        {{ page.props.auth.user.name }}
                    </span>
                    <i class="pi pi-angle-down text-xs text-corebanx-preto/40" />
                </button>

                <Menu ref="menuUsuario" :model="itensUsuario" :popup="true" />
            </header>

            <main class="p-4 sm:p-6">
                <slot />
            </main>
        </div>
    </div>
</template>
