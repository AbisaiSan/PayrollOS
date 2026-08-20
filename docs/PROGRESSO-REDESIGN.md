# Progresso do redesign de telas

> Ponto de retomada do `PLANO-DESENVOLVIMENTO-TELAS-PAYROLLOS.md`.
> Última sessão: 20/08/2026. **Concluídas 8 de 40 tarefas — Prioridade 1 fechada.**
> **Próxima: Tarefa 9, Reembolsos — listagem.**

---

## Como retomar

1. Leia este arquivo e o `PLANO-DESENVOLVIMENTO-TELAS-PAYROLLOS.md`
2. Abra o `PayrollOS - Prototipo de Design.html` na tela de número da tarefa
3. Siga o protocolo do plano: uma tarefa por vez, parar ao terminar, pedir
   aprovação antes de commitar, perguntar antes de seguir

### Ambiente

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
C:\php83\php.exe vendor/bin/pest      # 64 testes
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
| 7 | Pagamento, formulário | `6be3346` (backend) + `35d6be4` (tela) |
| 8 | Dashboard | `e350337` |

O núcleo operacional está inteiro: dá para lançar um pagamento, achá-lo na
listagem, abrir o detalhe, mudar status, confirmar e ver tudo registrado na
linha do tempo.

---

## Infraestrutura que as próximas 32 tarefas herdam

Construída durante a Prioridade 1. **Reaproveite em vez de recriar.**

### Tokens (`tailwind.config.js`)

- Paleta da marca com as duas escalas de 10 tons: `laranja-*`, `azul-*`
- Atalhos: `corebanx-laranja`, `corebanx-azul`, `corebanx-preto`, `corebanx-cinza`
- Tinta com opacidades: `ink`, `ink-90`, `ink-70`, `ink-55`, `ink-35`, `ink-16`, `ink-8`
- Fundo da aplicação: `app-bg`
- Semânticas de status, cada uma com texto, fundo e linha:
  `sucesso`, `info`, `atencao`, `perigo`, `neutro`
  (ex.: `bg-perigo-bg text-perigo border-perigo-line`)
- Tipografia: `font-sans` = IBM Plex Sans, `font-mono` = IBM Plex Mono
- Medidas: `w-sidebar` (256px), `h-topbar` (64px)
- Sombras: `shadow-card`, `shadow-pop`

### Componentes (`resources/js/Components/`)

| Componente | Para quê |
|---|---|
| `Icone.vue` | Set de 39 ícones do protótipo. Ícone desconhecido cai em `info` |
| `StatusBadge.vue` | Chip de status com ícone próprio por severidade |
| `CardIndicador.vue` | Cartão de número, com estado de alerta opcional |
| `CabecalhoPagina.vue` | Título + descrição + slot `acoes`, vai no slot `header` do layout |
| `Aviso.vue` | Faixa de aviso, tons `neutro` e `atencao` |
| `HistoricoStatusTimeline.vue` | Linha do tempo de status, **já serve reembolsos** |
| `ContasBancarias.vue` | Painel de contas e Pix (ainda no visual antigo, é a Tarefa 17) |
| `ModalConfirmarPagamento.vue` | Confirmação manual de pagamento |
| `ModalMudarStatusPagamento.vue` | Mudança de status por transições permitidas |

### Composables (`resources/js/Composables/`)

- `useFormato` — `formatarMoeda`, `formatarData`, `formatarDataHora`,
  `formatarCompetencia`, `formatarDocumento`, `diasAte`, `vencimentoRelativo`,
  `resumoConta`, `paraDate`, `paraIso`
- `usePermissoes` — `pode()`, `podeAlguma()`, `ehAdministrador`
- `useDebounce` — `debounce()`

### CSS global (`resources/css/app.css`)

Já resolvido, não repita nas telas:

- `.mono` — fonte monoespaçada + numeral tabular
- Gramática de tabela do `DataTable`: cabeçalho em caixa alta miúda, hairlines,
  hover em laranja claro, página ativa em azul
- Toast e ConfirmDialog no visual do protótipo
- Abaixo de 1024px: botões da topbar viram só ícone (via classe `topbar-acoes`),
  toast ocupa a largura útil

### Backend acrescentado

`App\Support\Navegacao` — estrutura do menu em 3 grupos, filtrada por permissão e
entregue pronta ao frontend via Inertia. **Ao adicionar item de menu, mexa aqui**,
não na Vue.

`BeneficiarioController` — dois endpoints JSON criados na Tarefa 7:

| Rota | Devolve |
|---|---|
| `beneficiarios.buscar` | Beneficiários ativos que casam com o termo, máx. 20 |
| `beneficiarios.dados` | Contas ativas e contratos ativos do beneficiário |

Servem qualquer formulário que precise escolher beneficiário — a Tarefa 11
(reembolso) deve reaproveitá-los.

---

## Decisões tomadas que fogem do protótipo ou do plano

Registradas para não serem revertidas por engano.

1. **Sino de notificações omitido** da topbar. O protótipo tem, mas não há backend
   de notificações e o briefing as coloca fora de escopo. Botão morto lê pior que
   ausência.

2. **Nenhuma transição vem pré-selecionada** no modal de mudança de status, e o
   botão de salvar fica desabilitado até haver escolha. O protótipo marcava a
   primeira; numa tela que muda estado de dinheiro, um clique distraído aplicaria
   uma transição que ninguém escolheu.

3. **Menu vem do backend**, não de constantes espelhadas na Vue. O plano proíbe
   hardcodar slugs de permissão no frontend.

4. **Barras do dashboard são proporcionais à maior categoria**, não ao total
   (isto segue o protótipo). Contra o total, a folha esmaga as demais.

5. **Conta principal já vem selecionada** no formulário de pagamento ao escolher
   o beneficiário.

---

## Pendências conhecidas

### Desvios de escopo a revisar

- **Correções de backend fora do escopo.** O plano reserva alterações de backend
  às Tarefas 28 e 37, mas a Tarefa 7 corrigiu dois defeitos que ela própria
  expôs: busca case-insensitive (`LIKE` no PostgreSQL não achava "Marina" ao
  digitar "marina", afetando listagens já entregues) e contas com dígito `"0"`
  perdendo o dígito. Ambos em `6be3346`.

- **`resumoConta` duplicado.** A formatação "Itaú, Ag. 1234, C/C 56789-0" existe
  no accessor de `ContaBancaria` e no helper do frontend. Some adicionando
  `$appends = ['resumo']` ao model, se quiser unificar.

### Tarefas que exigem decisão antes de começar

- **Tarefa 28, exportação de relatórios.** A rota `relatorios.exportar` existe mas
  responde `501`. Construir o modal com a ação desabilitada, conforme o plano.

- **Tarefa 37, usuários.** Não há controller, rota nem tela, e a permissão
  `usuarios.gerenciar` existe e é concedida ao Administrador. Como não há cadastro
  público, hoje **o administrador não consegue dar acesso a ninguém pela
  interface** — só rodando o seeder. Decidir se o backend entra na mesma tarefa
  ou numa anterior.

### Instruções específicas já anotadas no plano

- **Tarefa 9** não deve ter filtro de "minha equipe" nem recorte por hierarquia de
  gestor: `reembolsos.ver` hoje libera visão geral e o backend não sustenta o
  recorte.
- **Tarefa 10** reaproveita `HistoricoStatusTimeline`, não recria.
- **Tarefa 40** vira retrofit nas listagens já feitas (3, 9, 13, 19, 22, 25, 29).

---

## Estado do banco

Limpo, como instalação nova: 4 perfis, 21 permissões, 12 categorias e o usuário
administrador. Todos os dados usados para testar as telas foram removidos ao fim
de cada tarefa.

Para navegar com dados, semeie um cenário e limpe depois — nenhuma tarefa deixou
resíduo no banco.
