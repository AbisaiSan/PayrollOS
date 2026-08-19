<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fornecedores', function (Blueprint $table) {
            $table->id();
            $table->string('tipo_pessoa', 2);
            // Razao social para PJ, nome completo para PF.
            $table->string('razao_social');
            $table->string('nome_fantasia')->nullable();
            // CPF (11) ou CNPJ (14), somente digitos.
            $table->string('documento', 14)->unique();
            $table->string('tipo_fornecedor', 20);
            $table->string('email')->nullable();
            $table->string('telefone', 20)->nullable();
            $table->string('endereco')->nullable();
            $table->string('status', 20)->default('ativo');
            $table->text('observacoes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('status');
            $table->index('tipo_fornecedor');
            $table->index('razao_social');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fornecedores');
    }
};
