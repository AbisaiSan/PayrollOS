<?php

namespace App\Http\Controllers;

use App\Models\Anexo;
use App\Models\Contrato;
use App\Models\Pagamento;
use App\Models\Reembolso;
use App\Services\AnexoService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Comprovantes e documentos (regra 3.8).
 */
class AnexoController extends Controller
{
    public function __construct(private readonly AnexoService $service) {}

    public function store(Request $request, string $tipoRegistro, int $registroId): RedirectResponse
    {
        $registro = $this->resolverRegistro($tipoRegistro, $registroId);

        // Anexar herda a permissao de gerenciar o registro dono do anexo.
        $this->authorize('update', $registro);

        $request->validate([
            'arquivo' => [
                'required',
                'file',
                'max:'.config('payrollos.anexos.tamanho_maximo_kb'),
                'mimes:'.implode(',', config('payrollos.anexos.mimes')),
            ],
            'nome' => ['nullable', 'string', 'max:255'],
        ]);

        $this->service->anexar($registro, $request->file('arquivo'), $request->input('nome'));

        return back()->with('sucesso', 'Anexo enviado.');
    }

    /**
     * Download autenticado: o arquivo nunca fica em disco publico porque
     * comprovante carrega dado bancario.
     */
    public function download(Anexo $anexo): StreamedResponse
    {
        $this->authorize('view', $anexo->anexavel);

        abort_unless(Storage::disk($anexo->disco)->exists($anexo->caminho_arquivo), 404);

        return Storage::disk($anexo->disco)->download($anexo->caminho_arquivo, $anexo->nome_arquivo);
    }

    public function destroy(Anexo $anexo): RedirectResponse
    {
        $this->authorize('update', $anexo->anexavel);

        $this->service->remover($anexo);

        return back()->with('sucesso', 'Anexo removido.');
    }

    private function resolverRegistro(string $tipo, int $id): Model
    {
        return match ($tipo) {
            'pagamento' => Pagamento::findOrFail($id),
            'reembolso' => Reembolso::findOrFail($id),
            'contrato' => Contrato::findOrFail($id),
        };
    }
}
