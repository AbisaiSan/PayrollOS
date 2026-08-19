import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';
import primeui from 'tailwindcss-primeui';

/** @type {import('tailwindcss').Config} */
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
                // Identidade visual Corebanx (secao 5.1 do plano).
                corebanx: {
                    laranja: '#F37B46',
                    azul: '#214396',
                    preto: '#0D0E0E',
                    cinza: '#EFEFEE',
                },
                // Escalas derivadas, para estados de hover/foco sem cores soltas no markup.
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
            },
            fontFamily: {
                sans: ['Figtree', ...defaultTheme.fontFamily.sans],
            },
        },
    },

    plugins: [forms, primeui],
};
