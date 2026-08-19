<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contratos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fornecedor_id')->constrained('fornecedores')->cascadeOnUpdate()->restrictOnDelete();
            $table->foreignId('categoria_id')->nullable()->constrained('categorias_pagamento')->nullOnDelete();
            $table->foreignId('conta_bancaria_id')->nullable()->constrained('contas_bancarias')->nullOnDelete();
            $table->string('descricao');
            $table->string('tipo', 20);
            $table->decimal('valor', 15, 2);
            $table->string('periodicidade', 20)->nullable();
            $table->unsignedTinyInteger('dia_vencimento')->nullable();
            $table->date('data_inicio');
            $table->date('data_fim')->nullable();
            // Ultima competencia ja gerada, evita duplicar lancamento se a rotina rodar duas vezes.
            $table->date('proximo_vencimento')->nullable();
            $table->string('status', 20)->default('ativo');
            $table->text('observacoes')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index('proximo_vencimento');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contratos');
    }
};
