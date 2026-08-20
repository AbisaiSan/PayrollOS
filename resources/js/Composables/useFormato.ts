import dayjs from 'dayjs';
import 'dayjs/locale/pt-br';
import customParseFormat from 'dayjs/plugin/customParseFormat';
import type { ContaBancaria } from '@/types';

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

    /** "hoje", "amanha", "em 3 dias", "vencido ha 5 dias". */
    const vencimentoRelativo = (data: string | null | undefined) => {
        const dias = diasAte(data);

        if (dias === null) return '';
        if (dias === 0) return 'hoje';
        if (dias === 1) return 'amanhã';
        if (dias === -1) return 'venceu ontem';
        if (dias < 0) return `venceu há ${Math.abs(dias)} dias`;

        return `em ${dias} dias`;
    };

    /**
     * Resumo legivel da conta: "Itau, Ag. 1234, C/C 56789-0".
     *
     * A listagem recebe isto pronto do backend; nas telas que recebem a conta como
     * objeto, monta-se aqui.
     */
    const resumoConta = (conta: ContaBancaria | null | undefined) => {
        if (!conta) return '—';

        // Comparar com null/vazio: o digito "0" e falsy e a conta sairia como
        // "56789" em vez de "56789-0".
        const temDigito = conta.digito !== null && conta.digito !== '';
        const numero = temDigito ? `${conta.conta}-${conta.digito}` : conta.conta;
        const tipo = conta.tipo_conta === 'poupanca' ? 'Poup.' : 'C/C';

        return `${conta.banco}, Ag. ${conta.agencia}, ${tipo} ${numero}`;
    };

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
        vencimentoRelativo,
        resumoConta,
        paraDate,
        paraIso,
    };
}
