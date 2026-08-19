<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('anexos', function (Blueprint $table) {
            $table->id();
            // Pagamento, Reembolso ou Contrato (regra 3.8).
            $table->morphs('anexavel');
            $table->string('nome_arquivo');
            $table->string('caminho_arquivo');
            $table->string('disco', 30)->default('local');
            $table->string('tipo_arquivo', 100)->nullable();
            $table->unsignedBigInteger('tamanho')->default(0);
            $table->foreignId('enviado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('anexos');
    }
};
