<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reembolsos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('colaborador_id')->constrained('colaboradores')->restrictOnDelete();
            $table->foreignId('conta_bancaria_id')->nullable()->constrained('contas_bancarias')->nullOnDelete();
            $table->string('descricao');
            $table->string('categoria', 20);
            $table->decimal('valor', 15, 2);
            $table->date('data_solicitacao');
            $table->date('data_pagamento')->nullable();
            $table->string('status', 20)->default('pendente');
            // Quem registrou a solicitacao: o proprio colaborador ou o financeiro em nome dele (regra 3.7).
            $table->foreignId('solicitado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->text('observacoes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('status');
            $table->index('data_solicitacao');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reembolsos');
    }
};
