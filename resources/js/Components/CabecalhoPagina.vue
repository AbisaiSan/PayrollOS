<script setup lang="ts">
defineProps<{
    titulo: string;
    descricao?: string;
}>();
</script>

<template>
    <div class="flex items-center gap-3">
        <div class="min-w-0">
            <h1 class="truncate text-[16.5px] font-semibold -tracking-[0.01em] text-ink">
                {{ titulo }}
            </h1>
            <p v-if="descricao" class="mt-px truncate text-[12.5px] text-ink-55">
                {{ descricao }}
            </p>
        </div>

        <!--
            Ações da tela: ficam à direita do título, logo antes do menu do usuário.
            A classe topbar-acoes é o gancho que reduz os botões a só ícone abaixo
            de 1024px (ver app.css) — sem ela o título perde espaço no celular.
        -->
        <div v-if="$slots.acoes" class="topbar-acoes ml-auto flex shrink-0 items-center gap-2.5">
            <slot name="acoes" />
        </div>
    </div>
</template>
