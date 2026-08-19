<script setup lang="ts">
defineProps<{
    titulo: string;
    descricao?: string;
}>();
</script>

<template>
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div class="min-w-0">
            <h1 class="truncate text-lg font-semibold text-corebanx-preto">
                {{ titulo }}
            </h1>
            <p v-if="descricao" class="mt-0.5 truncate text-sm text-corebanx-preto/50">
                {{ descricao }}
            </p>
        </div>

        <div v-if="$slots.acoes" class="flex items-center gap-2">
            <slot name="acoes" />
        </div>
    </div>
</template>
