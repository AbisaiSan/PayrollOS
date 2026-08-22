<script setup lang="ts">
import { computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import Button from 'primevue/button';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import CabecalhoPagina from '@/Components/CabecalhoPagina.vue';
import Icone from '@/Components/Icone.vue';
import { usePermissoes } from '@/Composables/usePermissoes';

const { perfis } = usePermissoes();

const ROTULO_PERFIL: Record<string, string> = {
    administrador: 'Administrador',
    financeiro: 'Financeiro',
    gestor: 'Gestor',
    leitura: 'Leitura',
};

/**
 * Nomear o perfil do usuário transforma "acesso negado" em algo acionável: ele
 * sabe o que pedir ao administrador em vez de só bater na porta.
 */
const perfilAtual = computed(() =>
    perfis.value.map((perfil) => ROTULO_PERFIL[perfil] ?? perfil).join(', '),
);
</script>

<template>
    <Head title="Acesso restrito" />

    <AuthenticatedLayout>
        <template #header>
            <CabecalhoPagina titulo="Acesso restrito" />
        </template>

        <div class="flex flex-col items-center px-4 pb-10 pt-16 text-center">
            <span
                class="mb-4 flex h-14 w-14 items-center justify-center rounded-full bg-atencao-bg text-atencao"
            >
                <Icone nome="lock" :tamanho="24" />
            </span>

            <h2 class="text-[17px] font-semibold">Sem permissão para acessar esta tela</h2>

            <p class="mt-2 max-w-[420px] text-[13px] leading-[1.55] text-ink-55">
                <template v-if="perfilAtual">
                    Seu perfil (<strong class="font-semibold text-ink-70">{{ perfilAtual }}</strong
                    >) não inclui acesso a este módulo.
                </template>
                <template v-else> Seu perfil não inclui acesso a este módulo. </template>
                Se você acredita que deveria ter acesso, fale com um administrador.
            </p>

            <Link :href="route('dashboard')" class="mt-5">
                <Button label="Voltar ao Dashboard" size="small">
                    <template #icon><Icone nome="home" :tamanho="16" /></template>
                </Button>
            </Link>
        </div>
    </AuthenticatedLayout>
</template>
