<script setup lang="ts">
withDefaults(defineProps<{ compacto?: boolean }>(), { compacto: false });
</script>

<template>
    <div class="flex items-center gap-2.5">
        <svg
            viewBox="0 0 32 32"
            class="h-8 w-8 shrink-0"
            role="img"
            aria-label="Corebanx"
        >
            <rect width="32" height="32" rx="8" fill="#214396" />
            <path
                d="M9 20.5V11.5a1.5 1.5 0 0 1 1.5-1.5h5.2a5.3 5.3 0 0 1 0 10.6h-2.4"
                fill="none"
                stroke="#F37B46"
                stroke-width="2.6"
                stroke-linecap="round"
            />
            <circle cx="21.5" cy="21" r="2" fill="#F37B46" />
        </svg>

        <div v-if="!compacto" class="leading-none">
            <span class="block text-base font-semibold tracking-tight text-white">
                PayrollOS
            </span>
            <span class="block text-[11px] font-medium uppercase tracking-widest text-white/50">
                Corebanx
            </span>
        </div>
    </div>
</template>
