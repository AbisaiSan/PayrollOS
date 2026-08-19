<?php

namespace Database\Seeders;

use App\Enums\TipoCategoria;
use App\Models\CategoriaPagamento;
use Illuminate\Database\Seeder;

/**
 * Categorias iniciais. O financeiro pode renomear, desativar e criar outras sem
 * depender de deploy (regra 3.5).
 */
class CategoriaPagamentoSeeder extends Seeder
{
    public function run(): void
    {
        $categorias = [
            ['nome' => 'Salário', 'tipo' => TipoCategoria::Salario],
            ['nome' => 'Adiantamento salarial', 'tipo' => TipoCategoria::Salario],
            ['nome' => 'Férias', 'tipo' => TipoCategoria::Ferias],
            ['nome' => 'Décimo terceiro', 'tipo' => TipoCategoria::DecimoTerceiro],
            ['nome' => 'Rescisão', 'tipo' => TipoCategoria::Rescisao],
            ['nome' => 'Fornecedor', 'tipo' => TipoCategoria::Fornecedor],
            ['nome' => 'Prestação de serviço', 'tipo' => TipoCategoria::Servico],
            ['nome' => 'Aluguel', 'tipo' => TipoCategoria::Servico],
            ['nome' => 'Licença de software', 'tipo' => TipoCategoria::Servico],
            ['nome' => 'Contabilidade', 'tipo' => TipoCategoria::Servico],
            ['nome' => 'Reembolso', 'tipo' => TipoCategoria::Reembolso],
            ['nome' => 'Outros', 'tipo' => TipoCategoria::Outro],
        ];

        foreach ($categorias as $categoria) {
            CategoriaPagamento::updateOrCreate(
                ['nome' => $categoria['nome']],
                ['tipo' => $categoria['tipo'], 'ativo' => true],
            );
        }
    }
}
