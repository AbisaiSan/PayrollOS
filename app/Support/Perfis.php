<?php

namespace App\Support;

/**
 * Perfis e permissoes do PayrollOS (regra 3.9).
 *
 * Centralizado aqui para que o seeder, as policies e o frontend usem a mesma
 * fonte, em vez de strings soltas espalhadas pelo codigo.
 */
final class Perfis
{
    public const ADMINISTRADOR = 'administrador';

    public const FINANCEIRO = 'financeiro';

    public const GESTOR = 'gestor';

    public const LEITURA = 'leitura';

    /**
     * @return array<int, string>
     */
    public static function todos(): array
    {
        return [self::ADMINISTRADOR, self::FINANCEIRO, self::GESTOR, self::LEITURA];
    }

    public static function rotulo(string $perfil): string
    {
        return match ($perfil) {
            self::ADMINISTRADOR => 'Administrador',
            self::FINANCEIRO => 'Financeiro',
            self::GESTOR => 'Gestor',
            self::LEITURA => 'Leitura',
            default => $perfil,
        };
    }

    /**
     * Permissoes concedidas a cada perfil.
     *
     * @return array<string, array<int, string>>
     */
    public static function permissoesPorPerfil(): array
    {
        $todas = Permissoes::todas();

        return [
            self::ADMINISTRADOR => $todas,

            self::FINANCEIRO => [
                Permissoes::COLABORADORES_VER,
                Permissoes::COLABORADORES_GERENCIAR,
                Permissoes::FORNECEDORES_VER,
                Permissoes::FORNECEDORES_GERENCIAR,
                Permissoes::CONTAS_VER,
                Permissoes::CONTAS_GERENCIAR,
                Permissoes::CONTRATOS_VER,
                Permissoes::CONTRATOS_GERENCIAR,
                Permissoes::CATEGORIAS_VER,
                Permissoes::CATEGORIAS_GERENCIAR,
                Permissoes::PAGAMENTOS_VER,
                Permissoes::PAGAMENTOS_GERENCIAR,
                Permissoes::PAGAMENTOS_CONFIRMAR,
                Permissoes::REEMBOLSOS_VER,
                Permissoes::REEMBOLSOS_GERENCIAR,
                Permissoes::REEMBOLSOS_APROVAR,
                Permissoes::RELATORIOS_VER,
                Permissoes::RELATORIOS_EXPORTAR,
                Permissoes::AUDITORIA_VER,
            ],

            self::GESTOR => [
                Permissoes::COLABORADORES_VER,
                Permissoes::FORNECEDORES_VER,
                Permissoes::CONTRATOS_VER,
                Permissoes::PAGAMENTOS_VER,
                Permissoes::REEMBOLSOS_VER,
                Permissoes::REEMBOLSOS_SOLICITAR,
                Permissoes::RELATORIOS_VER,
            ],

            self::LEITURA => [
                Permissoes::COLABORADORES_VER,
                Permissoes::FORNECEDORES_VER,
                Permissoes::CONTRATOS_VER,
                Permissoes::PAGAMENTOS_VER,
                Permissoes::REEMBOLSOS_VER,
                Permissoes::RELATORIOS_VER,
            ],
        ];
    }
}
