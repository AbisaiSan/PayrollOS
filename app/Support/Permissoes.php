<?php

namespace App\Support;

final class Permissoes
{
    public const COLABORADORES_VER = 'colaboradores.ver';

    public const COLABORADORES_GERENCIAR = 'colaboradores.gerenciar';

    public const FORNECEDORES_VER = 'fornecedores.ver';

    public const FORNECEDORES_GERENCIAR = 'fornecedores.gerenciar';

    public const CONTAS_VER = 'contas.ver';

    public const CONTAS_GERENCIAR = 'contas.gerenciar';

    public const CONTRATOS_VER = 'contratos.ver';

    public const CONTRATOS_GERENCIAR = 'contratos.gerenciar';

    public const CATEGORIAS_VER = 'categorias.ver';

    public const CATEGORIAS_GERENCIAR = 'categorias.gerenciar';

    public const PAGAMENTOS_VER = 'pagamentos.ver';

    public const PAGAMENTOS_GERENCIAR = 'pagamentos.gerenciar';

    /** Confirmar manualmente que o pagamento foi feito (regra 3.6). */
    public const PAGAMENTOS_CONFIRMAR = 'pagamentos.confirmar';

    public const REEMBOLSOS_VER = 'reembolsos.ver';

    public const REEMBOLSOS_SOLICITAR = 'reembolsos.solicitar';

    public const REEMBOLSOS_GERENCIAR = 'reembolsos.gerenciar';

    public const REEMBOLSOS_APROVAR = 'reembolsos.aprovar';

    public const RELATORIOS_VER = 'relatorios.ver';

    public const RELATORIOS_EXPORTAR = 'relatorios.exportar';

    public const AUDITORIA_VER = 'auditoria.ver';

    public const USUARIOS_GERENCIAR = 'usuarios.gerenciar';

    /**
     * @return array<int, string>
     */
    public static function todas(): array
    {
        return [
            self::COLABORADORES_VER,
            self::COLABORADORES_GERENCIAR,
            self::FORNECEDORES_VER,
            self::FORNECEDORES_GERENCIAR,
            self::CONTAS_VER,
            self::CONTAS_GERENCIAR,
            self::CONTRATOS_VER,
            self::CONTRATOS_GERENCIAR,
            self::CATEGORIAS_VER,
            self::CATEGORIAS_GERENCIAR,
            self::PAGAMENTOS_VER,
            self::PAGAMENTOS_GERENCIAR,
            self::PAGAMENTOS_CONFIRMAR,
            self::REEMBOLSOS_VER,
            self::REEMBOLSOS_SOLICITAR,
            self::REEMBOLSOS_GERENCIAR,
            self::REEMBOLSOS_APROVAR,
            self::RELATORIOS_VER,
            self::RELATORIOS_EXPORTAR,
            self::AUDITORIA_VER,
            self::USUARIOS_GERENCIAR,
        ];
    }
}
