<script setup lang="ts">
import { computed } from 'vue';

/**
 * Set de ícones do protótipo — traços de 1.6 em grade 20x20, herdando
 * `currentColor`.
 *
 * Vive aqui, e não no PrimeIcons, porque o desenho dos ícones de status faz parte
 * do vocabulário visual: é o que permite distinguir "pago" de "atrasado" sem
 * depender da cor.
 */
const CAMINHOS: Record<string, string> = {
    // navegação
    home: '<path d="M3 10.5 10 4l7 6.5"/><path d="M5 9v7a1 1 0 0 0 1 1h3v-5h2v5h3a1 1 0 0 0 1-1V9"/>',
    wallet: '<rect x="2.5" y="5.5" width="15" height="11" rx="2"/><path d="M2.5 8.5h15"/><circle cx="14" cy="12" r="1"/>',
    receipt: '<path d="M5 3h10v14l-2-1.2L11 17l-2-1.2L7 17l-2-1.2V3Z"/><path d="M7.5 7h5M7.5 10h5"/>',
    users: '<circle cx="7.5" cy="7" r="2.5"/><path d="M2.5 16c0-2.5 2.2-4 5-4s5 1.5 5 4"/><circle cx="14.5" cy="7.5" r="2"/><path d="M12.5 12.2c1.9.2 3.5 1.5 3.5 3.8"/>',
    briefcase: '<rect x="2.5" y="6.5" width="15" height="10" rx="2"/><path d="M7 6.5V5a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2v1.5"/><path d="M2.5 11h15"/>',
    file: '<path d="M6 2.5h6l3 3v12H6Z"/><path d="M12 2.5V6h3"/>',
    tags: '<path d="M3 6.5 10.5 3l6 3-3.5 7.5L3 17V6.5Z" transform="translate(0,-.5)"/><circle cx="7" cy="7.5" r="1"/>',
    chart: '<path d="M3 17V3"/><path d="M3 17h14"/><rect x="6" y="10" width="2.4" height="7"/><rect x="10.5" y="6.5" width="2.4" height="10.5"/><rect x="15" y="12.5" width="2" height="4.5"/>',
    history: '<path d="M3.5 10a6.5 6.5 0 1 0 2-4.7"/><path d="M2.5 3.5 3.3 6l2.5-.6"/><path d="M10 6.5V10l2.5 1.5"/>',

    // status
    clockOutline: '<circle cx="10" cy="10" r="7"/><path d="M10 6v4l2.6 1.6"/>',
    calendar: '<rect x="3" y="4.5" width="14" height="12" rx="2"/><path d="M3 8.5h14"/><path d="M7 2.5v3M13 2.5v3"/>',
    checkCircle: '<circle cx="10" cy="10" r="7"/><path d="M6.8 10.2l2.1 2.1 4.3-4.6"/>',
    alertTriangle: '<path d="M10 3.3 18 16H2Z"/><path d="M10 8.2v3.3"/><circle cx="10" cy="13.6" r="0.15" fill="currentColor" stroke-width="1.6"/>',
    slashCircle: '<circle cx="10" cy="10" r="7"/><path d="M5.5 5.5l9 9"/>',
    alertOctagon: '<path d="M6.3 2.5h7.4L18 6.8v6.4L13.7 17.5H6.3L2 13.2V6.8Z"/><path d="M10 6.5v4.3"/><path d="M10 13.6v.15"/>',
    info: '<circle cx="10" cy="10" r="7"/><path d="M10 9.2v4"/><circle cx="10" cy="6.7" r=".2" fill="currentColor" stroke-width="1.8"/>',

    // interface
    search: '<circle cx="8.5" cy="8.5" r="5.5"/><path d="M16.5 16.5 13 13"/>',
    filter: '<path d="M3 4h14l-5.5 6.2V16l-3 1.5v-7.3Z"/>',
    chevronDown: '<path d="M5 7.5 10 12.5 15 7.5"/>',
    chevronRight: '<path d="M7.5 5 12.5 10 7.5 15"/>',
    chevronLeft: '<path d="M12.5 5 7.5 10 12.5 15"/>',
    plus: '<path d="M10 4v12M4 10h12"/>',
    minus: '<path d="M4 10h12"/>',
    x: '<path d="M5 5l10 10M15 5 5 15"/>',
    menu: '<path d="M3 5.5h14M3 10h14M3 14.5h14"/>',
    logout: '<path d="M8 17H4.5a1 1 0 0 1-1-1V4a1 1 0 0 1 1-1H8"/><path d="M13 14l4-4-4-4"/><path d="M17 10H7.5"/>',
    user: '<circle cx="10" cy="7" r="3"/><path d="M4 17c0-3 2.7-5 6-5s6 2 6 5"/>',
    paperclip: '<path d="M13.5 6.5 8 12a2.2 2.2 0 0 0 3.1 3.1l6-6a3.8 3.8 0 0 0-5.4-5.4l-6 6a5.4 5.4 0 0 0 7.6 7.6"/>',
    download: '<path d="M10 3v10.5"/><path d="M6 10l4 4 4-4"/><path d="M4 16.5h12"/>',
    upload: '<path d="M10 13.5V3"/><path d="M6 7l4-4 4 4"/><path d="M4 16.5h12"/>',
    more: '<circle cx="4.5" cy="10" r="1.1"/><circle cx="10" cy="10" r="1.1"/><circle cx="15.5" cy="10" r="1.1"/>',
    bank: '<path d="M3 8.5 10 4l7 4.5"/><path d="M4 8.5h12v6.5H4Z"/><path d="M2.5 17h15"/><path d="M6.5 10.5v3.5M10 10.5v3.5M13.5 10.5v3.5"/>',
    zap: '<path d="M11 3 4.5 11.5H9.5L8.5 17 15.5 8.5H10.5Z"/>',
    eye: '<path d="M2 10s2.8-5 8-5 8 5 8 5-2.8 5-8 5-8-5-8-5Z"/><circle cx="10" cy="10" r="2.2"/>',
    edit: '<path d="M12.5 3.5 16 7l-9 9H3.5v-3.5Z"/>',
    trash: '<path d="M4 6h12"/><path d="M8 6V4.2A1.2 1.2 0 0 1 9.2 3h1.6A1.2 1.2 0 0 1 12 4.2V6"/><path d="M5.5 6 6.2 16a1 1 0 0 0 1 .9h5.6a1 1 0 0 0 1-.9L14.5 6"/>',
    lock: '<rect x="4.5" y="9" width="11" height="8" rx="2"/><path d="M6.5 9V6.5a3.5 3.5 0 0 1 7 0V9"/>',
    mail: '<rect x="2.5" y="4.5" width="15" height="11" rx="2"/><path d="M3 5.5 10 11l7-5.5"/>',
    shield: '<path d="M10 2.7 16.5 5v5.3c0 4-2.8 6.8-6.5 7.9-3.7-1.1-6.5-3.9-6.5-7.9V5Z"/><path d="M7.3 10 9.2 11.9 12.7 8"/>',
    refresh: '<path d="M16 10a6 6 0 1 1-2-4.5"/><path d="M16 3v3.5h-3.5"/>',
    building: '<rect x="4" y="3" width="9" height="14" rx="1"/><path d="M13 8h3v9h-3"/><path d="M6.5 6h1.5M6.5 9h1.5M6.5 12h1.5M9.7 6h1.5M9.7 9h1.5M9.7 12h1.5"/>',
};

const props = withDefaults(
    defineProps<{
        nome: string;
        tamanho?: number | string;
    }>(),
    { tamanho: 18 },
);

// Ícone desconhecido cai em "info" em vez de sumir sem aviso.
const caminho = computed(() => CAMINHOS[props.nome] ?? CAMINHOS.info);
</script>

<template>
    <svg
        :width="tamanho"
        :height="tamanho"
        viewBox="0 0 20 20"
        fill="none"
        stroke="currentColor"
        stroke-width="1.6"
        stroke-linecap="round"
        stroke-linejoin="round"
        aria-hidden="true"
        focusable="false"
        v-html="caminho"
    />
</template>
