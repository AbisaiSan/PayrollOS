# Progresso do redesign de telas

> Ponto de retomada do `PLANO-DESENVOLVIMENTO-TELAS-PAYROLLOS.md`.
> Última sessão: 22/08/2026. **Concluídas 40 de 40 tarefas.**
> **O redesign está fechado.** O que resta são as pendências listadas no fim.

---

## Ambiente

O PATH da máquina aponta para versões antigas. Use sempre:

```powershell
# PHP 8.3 (o do PATH é 8.1 e não roda Laravel 13)
C:\php83\php.exe artisan ...
C:\php83\php.exe C:\composer\composer.phar ...

# Node 22 (o do PATH é 18 e não instala o binding do Vite 8)
$env:Path = "$env:APPDATA\nvm\v22.20.0;" + $env:Path
```

PostgreSQL 15 já instalado e rodando. Banco `payrollos`, usuário `payrollos`,
senha no `.env`. O `psql` fica em `C:\Program Files\PostgreSQL\15\bin\`.

### Verificação antes de cada commit

```powershell
npx vue-tsc --noEmit          # sem saída = ok
npx vite build
C:\php83\php.exe vendor/bin/pint --test
C:\php83\php.exe vendor/bin/pest      # 75 testes
```

---

## Tarefas concluídas

| # | Tarefa | Commit |
|---|---|---|
| 1 | Casca da aplicação, sidebar expandida | `783893a` |
| 2 | Casca, drawer mobile | `754f1cc` |
| 3 | Pagamentos, listagem | `228244a` |
| 4 | Pagamento, detalhe | `c3258fe` |
| 5 | Pagamento, modal de confirmação | `a131166` |
| 6 | Pagamento, modal de mudança de status | `e9eb3ce` |
| 7 | Pagamento, formulário | `6be3346` + `35d6be4` |
| 8 | Dashboard | `e350337` |
| 9 | Reembolsos, listagem | `4311ff3` |
| 10 | Reembolso, detalhe | `39356b1` |
| 11 | Reembolso, formulário | `b876505` |
| 12 | Reembolso, modal de rejeição | `81d4aa3` |
| 13 | Colaboradores, listagem | `3437c36` |
| 14 | Colaborador, detalhe | `f0f4d93` |
| 15 | Colaborador, formulário | `ff08e1a` |
| 16 | Colaborador, modal de desligamento | `564415d` |
| 17 | Painel de contas bancárias | `7ee44c0` |
| 18 | Modal de conta bancária/Pix | `d4aaba9` |
| 19 | Fornecedores, listagem | `c5b7c20` |
| 20 | Fornecedor, detalhe | `9a9c499` |
| 21 | Fornecedor, formulário | `601ac12` |
| 22 | Contratos, listagem | `c25b6b7` |
| 23 | Contrato, detalhe | `69c2a7f` |
| 24 | Contrato, formulário | `04ec32d` |
| 25 | Categorias, listagem | `ee85e22` |
| 26 | Categoria, modal | `1d414af` |
| 27 | Relatórios | `e9eaa2c` |
| 28 | Relatórios, modal de exportação | `b5d87ca` |
| 29 | Auditoria | `9f84e47` |
| 30 | Componente de anexos | `6844e32` |
| 31–35 | Telas de autenticação | `e17ce63` |
| 36 | Meu perfil | `363c6c1` |
| 37 | Usuários, backend e telas | `43f5a47` |
| 38 | Erro 403 | `521c6f8` |
| 39 | Erro 404 | `024315f` |
| 40 | Estados de listagem | `6220375` |

As tarefas 31 a 35 saíram num commit só: as cinco telas compartilham o
`GuestLayout`, e restilizá-lo em etapas deixaria telas quebradas entre commits.

---

## Componentes e composables disponíveis

### Componentes (`resources/js/Components/`)

| Componente | Para quê |
|---|---|
| `Icone` | Set de 39 ícones do protótipo. Ícone desconhecido cai em `info` |
| `StatusBadge` | Chip de status. Cobre pagamento, reembolso, colaborador, fornecedor e contrato |
| `CardIndicador` | Cartão de número, com estado de alerta opcional |
| `CabecalhoPagina` | Título + descrição + slot `acoes` |
| `Aviso` | Faixa de aviso, tons `neutro` e `atencao` |
| `EstadoListagem` | Vazio por filtro, vazio de verdade e erro de carregamento |
| `TabelaEsqueleto` | Esqueleto de carregamento da grid |
| `HistoricoStatusTimeline` | Linha do tempo de status, serve pagamento e reembolso |
| `Anexos` | Upload, lista, download e remoção. Serve pagamento, reembolso e contrato |
| `ContasBancarias` | Painel de contas e Pix, serve colaborador e fornecedor |
| `ModalContaBancaria` | Cadastro e edição de conta/Pix |
| `ModalConfirmarPagamento` | Confirmação manual de pagamento |
| `ModalMudarStatusPagamento` | Mudança de status por transições permitidas |
| `ModalRejeitarReembolso` | Rejeição com motivo obrigatório |
| `ModalDesligarColaborador` | Desligamento com data e observações |
| `ModalCategoria` | Cadastro e edição de categoria |
| `ModalExportarRelatorio` | Escolha de formato — **ação desabilitada**, ver pendências |

### Composables (`resources/js/Composables/`)

- `useFormato` — `formatarMoeda`, `formatarData`, `formatarDataHora`,
  `formatarCompetencia`, `formatarDocumento`, `cpfValido`, `cnpjValido`,
  `diasAte`, `vencimentoRelativo`, `resumoConta`, `paraDate`, `paraIso`
- `useConsulta` — consulta de listagem com `carregando`, `erro` e `tentarNovamente`
- `usePermissoes` — `pode()`, `podeAlguma()`, `ehAdministrador`
- `useDebounce` — `debounce()`

`cpfValido` e `cnpjValido` espelham `App\Support\Documento`. Ambos foram
conferidos rodando os dois lados sobre os mesmos casos, com saída idêntica.

---

## Pendências

### 1. Exportação de relatórios (Tarefa 28) — **bloqueada por backend**

`relatorios.exportar` responde `501`. O modal existe e mostra o que sairia no
arquivo, mas o botão de exportar está desabilitado. Quando o endpoint existir,
basta trocar o botão desabilitado por um link para
`route('relatorios.exportar', {...filtros})`.

### 2. `Gate::before` do Administrador anula guardas de policy

`AppServiceProvider.php:46` concede tudo ao Administrador antes de qualquer
policy rodar. Isso já quebrou duas regras:

- **`CategoriaPagamentoPolicy::delete`** — categoria em uso deveria ser bloqueada;
  o administrador passa e recebe **erro 500** de chave estrangeira. **Continua
  aberto.**
- **`UserPolicy::alternarAtivo`** — o administrador conseguia desativar a própria
  conta. Contornado com `abort_if` explícito no `UsuarioController`.

Vale decidir se o `Gate::before` passa a respeitar uma lista de habilidades em
vez de contorná-las por inteiro. Enquanto não, qualquer guarda fina escrita numa
policy é inútil para o perfil que mais tem poder.

### 3. Revisar quando a Fase 7 fechar

O `ESTADO.md` marca "ajuste fino de perfis" como pendente. Isso afeta toda
condicional de esconder ação por permissão — vale revisitar as telas quando essa
fase terminar.

### 4. `resumoConta` duplicado

A formatação "Itaú, Ag. 1234, C/C 56789-0" existe no accessor de `ContaBancaria`
e no helper do frontend. Some adicionando `$appends = ['resumo']` ao model.

---

## Decisões que fogem do protótipo ou do plano

Registradas para não serem revertidas por engano.

1. **Sino de notificações omitido** da topbar. Não há backend de notificações.
2. **Nenhuma transição pré-selecionada** no modal de mudança de status.
3. **Menu vem do backend** (`App\Support\Navegacao`), não de constantes na Vue.
   Ao adicionar item de menu, mexa lá.
4. **Barras de categoria são proporcionais à maior categoria**, não ao total
   (dashboard e relatórios). Contra o total, a folha esmaga as demais.
5. **Conta principal já vem selecionada** nos formulários de lançamento.
6. **Contagem de contas nas listagens conta contas ativas**, não todas. Conta
   inativa não serve de destino, então contar todas mostraria número tranquilo
   para quem não tem como receber.
7. **`pago → aprovado` no reembolso chama-se "Desfazer pagamento"**, não
   "Aprovar": o serviço limpa a data de pagamento nesse caminho.
8. **Próximo vencimento fica oculto** em contrato suspenso ou encerrado. A rotina
   das 06:00 não gera nada para eles, e a data guardada é resíduo.
9. **Motivo de rejeição é obrigatório no backend**, não só na tela.
10. **`ativo` entra nas credenciais de login**. Sem isso, desativar um usuário
    não impediria o acesso.
11. **Categoria não tem página de formulário** — só o Dialog na listagem. As
    rotas `create` e `edit` foram removidas.

---

## Alterações de backend feitas durante o redesign

O plano reservava backend às Tarefas 28 e 37. Estas saíram fora disso, cada uma
aprovada no commit correspondente:

| Onde | O quê | Por quê |
|---|---|---|
| `ReembolsoController` | `opcoes.colaboradores`, `paraListagem` | Filtro prometido sem lista; data deslocava com o fuso |
| `ReembolsoController` | `observacao` obrigatória ao rejeitar | Regra do fluxo, não da interface |
| `ColaboradorController` | `withCount('contasAtivas')` | Conta inativa não é destino |
| `ColaboradorController` | `opcoesFormulario` aceita o registro | Colaborador desligado sumia do select ao editar |
| `FornecedorController` | `withCount('contasAtivas')` | Idem |
| `ContratoController` | `opcoes.fornecedores`, `paraListagem`, idem edição | Idem |
| `AuditoriaController` | `opcoes.usuarios` | Filtro prometido sem lista |
| `CategoriaPagamentoController` | Remoção de `create`/`edit` | O Dialog assumiu |
| `LoginRequest` | `ativo` nas credenciais | Ver decisão 10 |
| `bootstrap/app.php` | Telas de 403 e 404 | Tarefas 38 e 39 |

---

## Estado do banco

Limpo. Todos os dados usados para testar as telas foram removidos ao fim de cada
tarefa. Os testes de fumaça escritos durante o desenvolvimento também foram
removidos — o que ficou em `tests/` é a suíte permanente, com 75 testes.

Para navegar com dados, semeie um cenário e limpe depois.
