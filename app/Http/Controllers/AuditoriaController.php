<?php

namespace App\Http\Controllers;

use App\Support\Permissoes;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Activitylog\Models\Activity;

/**
 * Regra 3.10: quem alterou, quando e o que mudou.
 *
 * Le o activitylog do spatie; a trilha especifica de mudanca de status fica em
 * historico_status e aparece na tela de cada pagamento/reembolso.
 */
class AuditoriaController extends Controller
{
    public function __invoke(Request $request): Response
    {
        abort_unless($request->user()->can(Permissoes::AUDITORIA_VER), 403);

        $atividades = Activity::query()
            ->with('causer:id,name')
            ->when($request->filled('log'), fn ($q) => $q->where('log_name', $request->string('log')))
            ->when($request->filled('usuario_id'), fn ($q) => $q->where('causer_id', $request->integer('usuario_id')))
            ->when($request->filled('inicio'), fn ($q) => $q->whereDate('created_at', '>=', $request->string('inicio')))
            ->when($request->filled('fim'), fn ($q) => $q->whereDate('created_at', '<=', $request->string('fim')))
            ->latest()
            ->paginate($request->integer('por_pagina', 30))
            ->withQueryString()
            ->through(fn (Activity $atividade) => [
                'id' => $atividade->id,
                'log' => $atividade->log_name,
                'descricao' => $atividade->description,
                'registro_tipo' => $atividade->subject_type,
                'registro_id' => $atividade->subject_id,
                'usuario' => $atividade->causer?->name,
                'alteracoes' => $atividade->properties,
                'created_at' => $atividade->created_at?->toIso8601String(),
            ]);

        return Inertia::render('Auditoria/Index', [
            'atividades' => $atividades,
            'filtros' => $request->only(['log', 'usuario_id', 'inicio', 'fim']),
            'opcoes' => [
                'logs' => [
                    ['value' => 'colaborador', 'label' => 'Colaboradores'],
                    ['value' => 'fornecedor', 'label' => 'Fornecedores'],
                    ['value' => 'conta_bancaria', 'label' => 'Contas bancárias'],
                    ['value' => 'contrato', 'label' => 'Contratos'],
                    ['value' => 'pagamento', 'label' => 'Pagamentos'],
                    ['value' => 'reembolso', 'label' => 'Reembolsos'],
                ],
            ],
        ]);
    }
}
