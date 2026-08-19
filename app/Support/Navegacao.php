<?php

namespace App\Support;

use App\Models\User;

/**
 * Estrutura do menu lateral.
 *
 * Fica no backend de proposito: os slugs de permissao vivem em Permissoes e nao
 * devem ser repetidos como string solta no Vue. O frontend recebe a arvore ja
 * filtrada pelo que o usuario pode ver e so a renderiza.
 *
 * A ordem segue a regra do plano: o que se lanca todo dia vem antes do que se
 * cadastra uma vez.
 */
final class Navegacao
{
    /**
     * Grupos e itens, na ordem de exibicao.
     *
     * @return array<int, array{titulo: string, itens: array<int, array{rotulo: string, rota: string, icone: string, permissao: ?string}>}>
     */
    public static function estrutura(): array
    {
        return [
            [
                'titulo' => 'Operação diária',
                'itens' => [
                    self::item('Dashboard', 'dashboard', 'home', null),
                    self::item('Pagamentos', 'pagamentos.index', 'wallet', Permissoes::PAGAMENTOS_VER),
                    self::item('Reembolsos', 'reembolsos.index', 'receipt', Permissoes::REEMBOLSOS_VER),
                ],
            ],
            [
                'titulo' => 'Cadastros',
                'itens' => [
                    self::item('Colaboradores', 'colaboradores.index', 'users', Permissoes::COLABORADORES_VER),
                    self::item('Fornecedores', 'fornecedores.index', 'briefcase', Permissoes::FORNECEDORES_VER),
                    self::item('Contratos', 'contratos.index', 'file', Permissoes::CONTRATOS_VER),
                    self::item('Categorias', 'categorias.index', 'tags', Permissoes::CATEGORIAS_VER),
                ],
            ],
            [
                'titulo' => 'Análise',
                'itens' => [
                    self::item('Relatórios', 'relatorios.index', 'chart', Permissoes::RELATORIOS_VER),
                    self::item('Auditoria', 'auditoria.index', 'history', Permissoes::AUDITORIA_VER),
                ],
            ],
        ];
    }

    /**
     * A estrutura sem os itens que o usuario nao pode acessar. Grupos que ficam
     * vazios somem, para nao sobrar um rotulo de secao sem nada embaixo.
     *
     * @return array<int, array{titulo: string, itens: array<int, array<string, mixed>>}>
     */
    public static function paraUsuario(?User $usuario): array
    {
        if ($usuario === null) {
            return [];
        }

        $grupos = [];

        foreach (self::estrutura() as $grupo) {
            $itens = array_values(array_filter(
                $grupo['itens'],
                fn (array $item) => $item['permissao'] === null || $usuario->can($item['permissao']),
            ));

            if ($itens !== []) {
                $grupos[] = ['titulo' => $grupo['titulo'], 'itens' => $itens];
            }
        }

        return $grupos;
    }

    /**
     * @return array{rotulo: string, rota: string, icone: string, permissao: ?string}
     */
    private static function item(string $rotulo, string $rota, string $icone, ?string $permissao): array
    {
        return [
            'rotulo' => $rotulo,
            'rota' => $rota,
            'icone' => $icone,
            'permissao' => $permissao,
        ];
    }
}
