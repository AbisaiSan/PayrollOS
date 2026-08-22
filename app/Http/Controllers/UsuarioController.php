<?php

namespace App\Http\Controllers;

use App\Http\Requests\UsuarioRequest;
use App\Models\Colaborador;
use App\Models\User;
use App\Support\Perfis;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Contas de acesso ao sistema (regra 3.9).
 *
 * Nao ha cadastro publico: a conta so nasce aqui, pela mao de quem tem
 * usuarios.gerenciar. Desativar e o caminho no lugar de excluir — a trilha de
 * auditoria aponta para o usuario e ficaria sem resposta para "quem alterou".
 */
class UsuarioController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', User::class);

        $usuarios = User::query()
            ->with(['colaborador:id,nome,departamento', 'roles:id,name'])
            ->when($request->filled('busca'), fn ($q) => $q->where(function ($consulta) use ($request) {
                $termo = '%'.mb_strtolower($request->string('busca')->toString()).'%';

                // LOWER dos dois lados: LIKE no PostgreSQL e sensivel a caixa.
                $consulta->whereRaw('LOWER(name) LIKE ?', [$termo])
                    ->orWhereRaw('LOWER(email) LIKE ?', [$termo]);
            }))
            ->when($request->filled('perfil'), fn ($q) => $q->whereHas(
                'roles',
                fn ($consulta) => $consulta->where('name', $request->string('perfil'))
            ))
            ->when($request->filled('ativo'), fn ($q) => $q->where('ativo', $request->boolean('ativo')))
            ->orderBy('name')
            ->paginate($request->integer('por_pagina', 20))
            ->withQueryString()
            ->through(fn (User $usuario) => $this->paraListagem($usuario));

        return Inertia::render('Usuarios/Index', [
            'usuarios' => $usuarios,
            'filtros' => $request->only(['busca', 'perfil', 'ativo']),
            'opcoes' => ['perfis' => $this->opcoesDePerfil()],
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', User::class);

        return Inertia::render('Usuarios/Form', [
            'usuario' => null,
            'opcoes' => $this->opcoesFormulario(),
        ]);
    }

    public function store(UsuarioRequest $request): RedirectResponse
    {
        $this->authorize('create', User::class);

        $dados = $request->validated();

        $usuario = DB::transaction(function () use ($dados) {
            $usuario = User::create([
                'name' => $dados['name'],
                'email' => $dados['email'],
                'password' => $dados['password'],
                'colaborador_id' => $dados['colaborador_id'] ?? null,
                'ativo' => $dados['ativo'] ?? true,
            ]);

            $usuario->syncRoles([$dados['perfil']]);

            return $usuario;
        });

        return redirect()
            ->route('usuarios.index')
            ->with('sucesso', 'Conta de '.$usuario->name.' criada.');
    }

    public function edit(User $usuario): Response
    {
        $this->authorize('update', $usuario);

        $usuario->load('roles:id,name');

        return Inertia::render('Usuarios/Form', [
            'usuario' => [
                'id' => $usuario->id,
                'name' => $usuario->name,
                'email' => $usuario->email,
                'colaborador_id' => $usuario->colaborador_id,
                'ativo' => $usuario->ativo,
                'perfil' => $usuario->roles->first()?->name,
            ],
            'opcoes' => $this->opcoesFormulario($usuario),
        ]);
    }

    public function update(UsuarioRequest $request, User $usuario): RedirectResponse
    {
        $this->authorize('update', $usuario);

        $dados = $request->validated();

        DB::transaction(function () use ($usuario, $dados) {
            $usuario->fill([
                'name' => $dados['name'],
                'email' => $dados['email'],
                'colaborador_id' => $dados['colaborador_id'] ?? null,
            ]);

            // Senha em branco mantem a atual: exigir a senha para corrigir um nome
            // levaria a trocar a senha de alguem sem querer.
            if (filled($dados['password'] ?? null)) {
                $usuario->password = $dados['password'];
            }

            $usuario->save();

            $usuario->syncRoles([$dados['perfil']]);
        });

        return redirect()
            ->route('usuarios.index')
            ->with('sucesso', 'Conta atualizada.');
    }

    /**
     * Ativar e desativar em vez de excluir, pela mesma razao da classe: a conta
     * some da porta de entrada, o registro fica.
     */
    public function alternarAtivo(Request $request, User $usuario): RedirectResponse
    {
        $this->authorize('alternarAtivo', $usuario);

        // A checagem se repete aqui de proposito. O Gate::before do
        // AppServiceProvider concede tudo ao Administrador antes de qualquer
        // policy rodar, entao a guarda de UserPolicy::alternarAtivo nao alcanca
        // justamente quem mais precisa dela: sem isto, um administrador desativa
        // a propria conta e, sendo o unico, tranca o sistema para todo mundo.
        abort_if($request->user()->id === $usuario->id, 403);

        $usuario->update(['ativo' => ! $usuario->ativo]);

        return back()->with(
            'sucesso',
            $usuario->ativo
                ? 'Acesso de '.$usuario->name.' reativado.'
                : 'Acesso de '.$usuario->name.' desativado. O histórico permanece.'
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function paraListagem(User $usuario): array
    {
        $perfil = $usuario->roles->first()?->name;

        return [
            'id' => $usuario->id,
            'name' => $usuario->name,
            'email' => $usuario->email,
            'ativo' => $usuario->ativo,
            'perfil' => $perfil,
            'perfil_rotulo' => $perfil ? Perfis::rotulo($perfil) : null,
            'colaborador' => $usuario->colaborador
                ? [
                    'id' => $usuario->colaborador->id,
                    'nome' => $usuario->colaborador->nome,
                    'departamento' => $usuario->colaborador->departamento,
                ]
                : null,
        ];
    }

    /**
     * @return array<int, array{value: string, label: string}>
     */
    private function opcoesDePerfil(): array
    {
        return array_map(
            fn (string $perfil) => ['value' => $perfil, 'label' => Perfis::rotulo($perfil)],
            Perfis::todos()
        );
    }

    /**
     * Colaboradores ainda sem conta, mais o ja vinculado a esta — senao o campo
     * abriria em branco na edicao e pareceria que ninguem esta escolhido.
     *
     * @return array<string, mixed>
     */
    private function opcoesFormulario(?User $usuario = null): array
    {
        $vinculados = User::query()
            ->whereNotNull('colaborador_id')
            ->when($usuario, fn ($q) => $q->whereKeyNot($usuario->id))
            ->pluck('colaborador_id');

        return [
            'perfis' => $this->opcoesDePerfil(),
            'colaboradores' => Colaborador::query()
                ->whereNotIn('id', $vinculados)
                ->orderBy('nome')
                ->get(['id', 'nome', 'departamento']),
        ];
    }
}
