<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Usuario do sistema e colaborador sao coisas distintas (regra 3.9): nem todo
     * colaborador tem acesso, e alguns usuarios (TI, socios) podem nao ser colaboradores.
     * O vinculo opcional abaixo e o que permite, no futuro, o proprio colaborador
     * solicitar seus reembolsos.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('colaborador_id')
                ->nullable()
                ->after('id')
                ->constrained('colaboradores')
                ->nullOnDelete();

            $table->boolean('ativo')->default(true)->after('password');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('colaborador_id');
            $table->dropColumn('ativo');
        });
    }
};
