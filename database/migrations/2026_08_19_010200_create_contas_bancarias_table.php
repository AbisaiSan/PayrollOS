<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contas_bancarias', function (Blueprint $table) {
            $table->id();
            // Colaborador ou Fornecedor (regra 3.2, tabela compartilhada).
            $table->morphs('owner');
            $table->string('banco');
            $table->string('codigo_banco', 5)->nullable();
            $table->string('agencia', 10);
            $table->string('conta', 20);
            $table->string('digito', 2)->nullable();
            $table->string('tipo_conta', 20);
            $table->string('titular_nome');
            $table->string('titular_documento', 14);
            $table->string('chave_pix')->nullable();
            $table->string('tipo_chave_pix', 20)->nullable();
            $table->boolean('principal')->default(false);
            $table->string('status', 20)->default('ativa');
            $table->timestamps();

            $table->index('status');
        });

        // No maximo uma conta principal por beneficiario (regra 3.2). Garantido no
        // banco, alem da validacao em ContaBancariaService, porque uma corrida entre
        // duas requisicoes passaria pela checagem da aplicacao.
        $driver = DB::connection()->getDriverName();

        if (in_array($driver, ['pgsql', 'sqlite'], true)) {
            DB::statement(
                'CREATE UNIQUE INDEX contas_bancarias_principal_unica
                 ON contas_bancarias (owner_type, owner_id)
                 WHERE principal = true'
            );
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('contas_bancarias');
    }
};
