import forms from '@tailwindcss/forms';
import primeui from 'tailwindcss-primeui';

/**
 * Tokens de design do PayrollOS.
 *
 * A paleta da marca é fixa (seção 2 do BRIEFING-DESIGN.md). Os cinco tons
 * semânticos de status são independentes dela e vêm do protótipo — cada um traz
 * texto, fundo e borda próprios, porque o status também é lido pela forma do chip,
 * não só pela cor.
 *
 * @type {import('tailwindcss').Config}
 */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.{vue,ts}',
        './node_modules/primevue/**/*.{vue,js}',
    ],

    theme: {
        extend: {
            colors: {
                laranja: {
                    50: '#FEF3ED',
                    100: '#FDE2D3',
                    200: '#FAC4A7',
                    300: '#F7A57B',
                    400: '#F5904F',
                    500: '#F37B46',
                    600: '#DB5F26',
                    700: '#B14A1D',
                    800: '#87381A',
                    900: '#5C2712',
                },
                azul: {
                    50: '#EDF1FA',
                    100: '#D4DCF2',
                    200: '#A9B9E5',
                    300: '#7E95D8',
                    400: '#5372CB',
                    500: '#3457B4',
                    600: '#214396',
                    700: '#1A3577',
                    800: '#132758',
                    900: '#0C1939',
                },

                // Atalhos com o nome que o time usa ao falar da marca.
                corebanx: {
                    laranja: '#F37B46',
                    azul: '#214396',
                    preto: '#0D0E0E',
                    cinza: '#EFEFEE',
                },

                // Tinta e superfícies.
                ink: {
                    DEFAULT: '#0D0E0E',
                    90: 'rgba(13,14,14,.90)',
                    70: 'rgba(13,14,14,.70)',
                    55: 'rgba(13,14,14,.55)',
                    35: 'rgba(13,14,14,.35)',
                    16: 'rgba(13,14,14,.12)',
                    8: 'rgba(13,14,14,.07)',
                },
                'app-bg': '#EFEFEE',

                // Semânticas de status: texto, fundo e linha para cada severidade.
                sucesso: { DEFAULT: '#146C43', bg: '#E7F5EC', line: '#BFE3CC' },
                info: { DEFAULT: '#0E7C86', bg: '#E3F5F6', line: '#BEE4E7' },
                atencao: { DEFAULT: '#9A6300', bg: '#FBF0DC', line: '#F0DBA8' },
                perigo: { DEFAULT: '#B3261E', bg: '#FBEAE8', line: '#F3C6C1' },
                neutro: { DEFAULT: '#5B6169', bg: '#EEEFF0', line: '#D9DBDE' },
            },

            fontFamily: {
                sans: ['IBM Plex Sans', 'ui-sans-serif', 'system-ui', 'sans-serif'],
                // Todo número que entra em coluna usa esta família (regra do plano).
                mono: ['IBM Plex Mono', 'ui-monospace', 'SFMono-Regular', 'Menlo', 'monospace'],
            },

            borderRadius: {
                sm: '6px',
                md: '10px',
                lg: '14px',
            },

            boxShadow: {
                card: '0 1px 2px rgba(13,14,14,.06), 0 1px 1px rgba(13,14,14,.04)',
                pop: '0 12px 32px rgba(13,14,14,.18), 0 2px 8px rgba(13,14,14,.10)',
            },

            spacing: {
                sidebar: '256px',
                topbar: '64px',
            },
        },
    },

    plugins: [forms, primeui],
};
