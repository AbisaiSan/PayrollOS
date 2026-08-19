import { definePreset } from '@primeuix/themes';
import Aura from '@primeuix/themes/aura';

/**
 * Preset do PrimeVue com a identidade visual Corebanx.
 *
 * O laranja (#F37B46) e a cor de acao; o azul (#214396) fica reservado para o
 * cromo da navegacao, para nao competir com os botoes nas telas de lancamento.
 */
export const TemaCorebanx = definePreset(Aura, {
    semantic: {
        primary: {
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
            950: '#3B190C',
        },
        colorScheme: {
            light: {
                surface: {
                    0: '#FFFFFF',
                    50: '#FAFAFA',
                    100: '#EFEFEE',
                    200: '#E2E2E0',
                    300: '#CBCBC8',
                    400: '#A3A3A0',
                    500: '#7A7A77',
                    600: '#575754',
                    700: '#3D3D3B',
                    800: '#252524',
                    900: '#0D0E0E',
                    950: '#050505',
                },
                primary: {
                    color: '#F37B46',
                    contrastColor: '#FFFFFF',
                    hoverColor: '#DB5F26',
                    activeColor: '#B14A1D',
                },
                formField: {
                    borderRadius: '0.5rem',
                },
            },
            dark: {
                surface: {
                    0: '#FFFFFF',
                    50: '#F7F7F7',
                    100: '#E2E2E0',
                    200: '#CBCBC8',
                    300: '#A3A3A0',
                    400: '#7A7A77',
                    500: '#575754',
                    600: '#3D3D3B',
                    700: '#252524',
                    800: '#1A1B1B',
                    900: '#0D0E0E',
                    950: '#050505',
                },
                primary: {
                    color: '#F5904F',
                    contrastColor: '#0D0E0E',
                    hoverColor: '#F7A57B',
                    activeColor: '#FAC4A7',
                },
            },
        },
    },
});
