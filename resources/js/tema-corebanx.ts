import { definePreset } from '@primeuix/themes';
import Aura from '@primeuix/themes/aura';

/**
 * Preset do PrimeVue com os tokens do PayrollOS.
 *
 * O laranja (#F37B46) é a cor de ação; o azul (#214396) fica reservado para o
 * cromo da navegação, para não competir com os botões nas telas de lançamento.
 *
 * As superfícies saem do quase-preto da marca (#0D0E0E), então os cinzas puxam
 * para a tinta em vez de serem neutros genéricos.
 */
export const TemaCorebanx = definePreset(Aura, {
    primitive: {
        borderRadius: {
            none: '0',
            xs: '4px',
            sm: '6px',
            md: '8px',
            lg: '10px',
            xl: '14px',
        },
    },
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
        formField: {
            paddingX: '11px',
            paddingY: '9px',
            borderRadius: '8px',
            focusRing: {
                width: '2px',
                style: 'solid',
                color: '#5372CB',
                offset: '2px',
            },
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
                text: {
                    color: 'rgba(13,14,14,.90)',
                    mutedColor: 'rgba(13,14,14,.55)',
                },
                content: {
                    borderColor: 'rgba(13,14,14,.12)',
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
