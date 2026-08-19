<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pagamentos', function (Blueprint $table) {
            $table->id();
            // Colaborador ou Fornecedor (regra 3.6).
            $table->morphs('payable');
            $table->foreignId('categoria_id')->constrained('categorias_pagamento')->restrictOnDelete();
            $table->foreignId('contrato_id')->nullable()->constrained('contratos')->nullOnDelete();
            // Conta pode nao existir quando a forma de pagamento e dinheiro ou boleto.
            $table->foreignId('conta_bancaria_id')->nullable()->constrained('contas_bancarias')->restrictOnDelete();
            // Competencia da folha no formato AAAA-MM (ex: 2026-08).
            $table->string('competencia', 7)->nullable();
            $table->string('descricao');
            $table->decimal('valor', 15, 2);
            $table->date('data_vencimento');
            $table->date('data_pagamento')->nullable();
            $table->string('forma_pagamento', 20);
            $table->string('status', 20)->default('pendente');
            $table->foreignId('criado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('atualizado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->text('observacoes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('status');
            $table->index('data_vencimento');
            $table->index('competencia');
            // Consulta mais comum do dashboard: em aberto dentro de um periodo.
            $table->index(['status', 'data_vencimento']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pagamentos');
    }
};
