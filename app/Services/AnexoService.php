<?php

namespace App\Services;

use App\Models\Anexo;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

/**
 * Anexos e comprovantes (regra 3.8).
 *
 * Comprovante contem dado bancario, entao o arquivo nunca vai para disco publico:
 * o download passa por rota autenticada.
 */
class AnexoService
{
    private const DISCO = 'local';

    public function anexar(Model $registro, UploadedFile $arquivo, ?string $nome = null): Anexo
    {
        $pasta = $this->pastaPara($registro);
        $caminho = $arquivo->store($pasta, self::DISCO);

        return $registro->anexos()->create([
            'nome_arquivo' => $nome ?? $arquivo->getClientOriginalName(),
            'caminho_arquivo' => $caminho,
            'disco' => self::DISCO,
            'tipo_arquivo' => $arquivo->getClientMimeType(),
            'tamanho' => $arquivo->getSize(),
            'enviado_por' => Auth::id(),
        ]);
    }

    public function remover(Anexo $anexo): void
    {
        Storage::disk($anexo->disco)->delete($anexo->caminho_arquivo);

        $anexo->delete();
    }

    /**
     * Ex: anexos/pagamentos/2026/08.
     */
    private function pastaPara(Model $registro): string
    {
        $tipo = str($registro->getTable())->lower()->value();

        return 'anexos/'.$tipo.'/'.now()->format('Y/m');
    }
}
