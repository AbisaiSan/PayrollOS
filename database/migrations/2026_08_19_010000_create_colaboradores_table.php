<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('colaboradores', function (Blueprint $table) {
            $table->id();
            $table->string('nome');
            // Armazenado somente com digitos, para busca e unicidade previsiveis.
            $table->string('cpf', 11)->unique();
            $table->string('cargo');
            $table->string('departamento');
            $table->string('tipo_contrato', 20);
            $table->date('data_admissao');
            $table->date('data_desligamento')->nullable();
            $table->decimal('salario_base', 15, 2)->default(0);
            $table->string('email')->nullable();
            $table->string('telefone', 20)->nullable();
            $table->string('status', 20)->default('ativo');
            $table->text('observacoes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('status');
            $table->index('departamento');
            $table->index('nome');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('colaboradores');
    }
};
