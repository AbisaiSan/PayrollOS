<script setup lang="ts">
withDefaults(defineProps<{ compacto?: boolean }>(), { compacto: false });
</script>

<template>
    <div class="flex items-center gap-2.5">
        <svg
            width="30"
            height="30"
            viewBox="0 0 30 30"
            fill="none"
            class="shrink-0"
            role="img"
            aria-label="PayrollOS"
        >
            <rect width="30" height="30" rx="8" fill="#F37B46" />
            <path
                d="M8 21V9h6.2c3 0 4.8 1.7 4.8 4.4 0 2.7-1.9 4.4-4.9 4.4H11v3.2H8Zm3-5.7h2.9c1.3 0 2.1-.7 2.1-1.9 0-1.2-.8-1.9-2.1-1.9H11v3.8Z"
                fill="#fff"
            />
        </svg>

        <div v-if="!compacto" class="leading-none">
            <span class="block text-[16.5px] font-bold tracking-[0.01em] text-white">
                PayrollOS
            </span>
            <span
                class="mt-px block text-[10.5px] font-normal uppercase tracking-[0.04em] text-white/60"
            >
                Corebanx
            </span>
        </div>
    </div>
</template>
