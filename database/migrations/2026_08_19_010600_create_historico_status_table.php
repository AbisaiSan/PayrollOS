<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('historico_status', function (Blueprint $table) {
            $table->id();
            // Pagamento ou Reembolso (regra 3.6, auditoria interna).
            $table->morphs('historicoavel');
            $table->string('status_anterior', 20)->nullable();
            $table->string('status_novo', 20);
            $table->foreignId('usuario_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('observacao')->nullable();
            // Registro imutavel: so tem created_at.
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('historico_status');
    }
};
