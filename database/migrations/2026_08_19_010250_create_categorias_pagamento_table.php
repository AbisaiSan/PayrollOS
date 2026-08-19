<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categorias_pagamento', function (Blueprint $table) {
            $table->id();
            $table->string('nome')->unique();
            // Classificacao fixa usada pelos relatorios; o nome e livre (regra 3.5).
            $table->string('tipo', 30);
            $table->string('descricao')->nullable();
            $table->boolean('ativo')->default(true);
            $table->timestamps();

            $table->index('tipo');
            $table->index('ativo');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('categorias_pagamento');
    }
};
