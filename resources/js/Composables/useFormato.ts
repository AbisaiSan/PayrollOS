import dayjs from 'dayjs';
import 'dayjs/locale/pt-br';
import customParseFormat from 'dayjs/plugin/customParseFormat';

dayjs.extend(customParseFormat);
dayjs.locale('pt-br');

const moeda = new Intl.NumberFormat('pt-BR', {
    style: 'currency',
    currency: 'BRL',
});

/**
 * Formatacao de exibicao. Valores chegam do backend como string decimal
 * ("1234.50") para nao perder centavos na serializacao JSON.
 */
export function useFormato() {
    const formatarMoeda = (valor: string | number | null | undefined) => {
        if (valor === null || valor === undefined || valor === '') return '—';

        return moeda.format(Number(valor));
    };

    const formatarData = (data: string | null | undefined) =>
        data ? dayjs(data).format('DD/MM/YYYY') : '—';

    const formatarDataHora = (data: string | null | undefined) =>
        data ? dayjs(data).format('DD/MM/YYYY HH:mm') : '—';

    /** "2026-08" vira "Ago/2026". */
    const formatarCompetencia = (competencia: string | null | undefined) =>
        competencia ? dayjs(competencia, 'YYYY-MM').format('MMM/YYYY') : '—';

    const formatarDocumento = (documento: string | null | undefined) => {
        if (!documento) return '—';

        const digitos = documento.replace(/\D/g, '');

        if (digitos.length === 11) {
            return digitos.replace(/(\d{3})(\d{3})(\d{3})(\d{2})/, '$1.$2.$3-$4');
        }

        if (digitos.length === 14) {
            return digitos.replace(
                /(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/,
                '$1.$2.$3/$4-$5',
            );
        }

        return documento;
    };

    /** Dias ate o vencimento; negativo quando ja venceu. */
    const diasAte = (data: string | null | undefined) =>
        data ? dayjs(data).startOf('day').diff(dayjs().startOf('day'), 'day') : null;

    /**
     * O DatePicker do PrimeVue trabalha com Date; o backend espera AAAA-MM-DD.
     * Estes dois fazem a ponte nos dois sentidos.
     */
    const paraDate = (data: string | null | undefined): Date | null =>
        data ? dayjs(data).toDate() : null;

    const paraIso = (data: Date | null | undefined): string | null =>
        data ? dayjs(data).format('YYYY-MM-DD') : null;

    return {
        formatarMoeda,
        formatarData,
        formatarDataHora,
        formatarCompetencia,
        formatarDocumento,
        diasAte,
        paraDate,
        paraIso,
    };
}
