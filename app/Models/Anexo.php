<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Facades\Storage;

/**
 * Comprovantes e documentos (regra 3.8).
 */
class Anexo extends Model
{
    use HasFactory;

    protected $table = 'anexos';

    protected $fillable = [
        'nome_arquivo',
        'caminho_arquivo',
        'disco',
        'tipo_arquivo',
        'tamanho',
        'enviado_por',
    ];

    protected function casts(): array
    {
        return [
            'tamanho' => 'integer',
        ];
    }

    /**
     * Pagamento, Reembolso ou Contrato.
     */
    public function anexavel(): MorphTo
    {
        return $this->morphTo();
    }

    public function enviadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'enviado_por');
    }

    /**
     * URL temporaria de download. Comprovante nao fica em disco publico.
     */
    public function urlTemporaria(int $minutos = 5): string
    {
        return Storage::disk($this->disco)->temporaryUrl(
            $this->caminho_arquivo,
            now()->addMinutes($minutos)
        );
    }

    public function getTamanhoLegivelAttribute(): string
    {
        $bytes = $this->tamanho;

        foreach (['B', 'KB', 'MB', 'GB'] as $unidade) {
            if ($bytes < 1024) {
                return round($bytes, 1)." {$unidade}";
            }

            $bytes /= 1024;
        }

        return round($bytes, 1).' TB';
    }
}
