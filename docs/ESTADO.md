# Estado do projeto

Referência: [`plano-original.md`](plano-original.md).

## Pronto

### Fase 0, Fundação

- Laravel 13 + Inertia + Vue 3 + TypeScript + Tailwind, com PrimeVue e locale pt-BR
- Autenticação via Breeze (login, recuperação de senha). **Auto-cadastro removido**:
  é sistema interno, usuários são criados pelo administrador
- Perfis e permissões (`spatie/laravel-permission`) com o catálogo centralizado em
  `App\Support\Perfis` e `App\Support\Permissoes`, aplicados por Policy em cada módulo
- Layout base com a identidade Corebanx (laranja `#F37B46`, azul `#214396`,
  quase-preto `#0D0E0E`, cinza `#EFEFEE`), navegação filtrada por permissão
- Auditoria automática via `spatie/laravel-activitylog` nos modelos relevantes

### Modelo de dados completo (fases 1 a 5)

Todas as tabelas do plano estão migradas, com models, enums e relacionamentos:
colaboradores, fornecedores, `contas_bancarias` (polimórfica), contratos,
`categorias_pagamento`, pagamentos, reembolsos, `historico_status` (polimórfica) e
anexos (polimórfica).

Decisão de schema: as colunas de estado são `string` no banco com **enum PHP** no
model, em vez de `ENUM` nativo. Mesma semântica, mas adicionar um estado novo (por
exemplo, quando o fluxo de aprovação entrar) não exige alterar o tipo da coluna no
PostgreSQL.

### Regras de negócio implementadas e testadas

- **Conta principal única por beneficiário** — garantida no `ContaBancariaService`
  e por índice único parcial no banco, porque duas requisições simultâneas passariam
  pela checagem da aplicação
- **Primeira conta vira principal automaticamente**, senão um lançamento logo após o
  cadastro ficaria sem destino padrão
- **Contas são inativadas, nunca excluídas**, preservando o vínculo com os pagamentos
  já lançados
- **Colaborador desligado e fornecedor inativo não recebem novos lançamentos**, mas
  mantêm o histórico
- **Transições de status validadas** em pagamentos e reembolsos, com registro em
  `historico_status` (quem, de qual status para qual, quando)
- **Conta de destino tem que pertencer ao beneficiário** do lançamento
- **Geração automática de contratos recorrentes**, idempotente (rodar duas vezes não
  duplica) e capaz de recuperar vencimentos acumulados se a rotina ficar dias parada
- **Promoção automática para Atrasado** do que venceu sem confirmação

64 testes cobrindo essas regras (`php artisan test`).

### Interface

| Tela | Estado |
|---|---|
| Dashboard | Completo, com indicadores do mês, próximos vencimentos e quebra por categoria |
| Colaboradores (listagem, formulário, detalhe) | Completo, ainda no visual anterior ao redesign |
| Contas bancárias / Pix | Completo, componente reaproveitável entre colaborador e fornecedor |
| Demais telas | Marcadas com a fase em que entram; o backend correspondente já existe |

### Redesign de telas, em andamento

Segue o `PLANO-DESENVOLVIMENTO-TELAS-PAYROLLOS.md` e o
`PayrollOS - Prototipo de Design.html`. **8 das 40 tarefas concluídas — toda a
Prioridade 1.** O ponto de retomada, a infraestrutura acumulada e as decisões
tomadas estão em [`PROGRESSO-REDESIGN.md`](PROGRESSO-REDESIGN.md).

Já redesenhadas: casca da aplicação (sidebar, drawer mobile, topbar), dashboard,
e o módulo de pagamentos inteiro — listagem, detalhe, formulário de lançamento e
os modais de confirmação e de mudança de status.

### Acrescentado ao backend durante o redesign

- `App\Support\Navegacao` — estrutura do menu, filtrada por permissão e entregue
  pronta ao frontend. Item de menu novo entra aqui, não na Vue.
- `BeneficiarioController` — rotas `beneficiarios.buscar` e `beneficiarios.dados`,
  que respondem JSON. Existem porque o formulário de lançamento precisa consultar
  beneficiários e suas contas ativas enquanto o usuário digita, e não havia
  nenhuma rota JSON na aplicação.

### Dois defeitos corrigidos

- **Busca não funcionava no PostgreSQL.** Os scopes usavam `LIKE`, sensível a
  caixa nesse banco, então buscar "marina" não encontrava "Marina". Afetava as
  listagens de pagamentos, colaboradores e fornecedores. Agora usa `LOWER()` dos
  dois lados, portável entre os três drivers.
- **Contas com dígito `"0"` perdiam o dígito**, exibindo `56789` no lugar de
  `56789-0`, porque a string `"0"` é falsy em PHP e em JavaScript.

## Próximas fases

| Fase | Entrega | Backend |
|---|---|---|
| 2 | Telas de fornecedores e contratos | Pronto |
| 3 | Formulário e detalhe de pagamento, categorias, upload de comprovante | Pronto |
| 4 | Telas de reembolso | Pronto |
| 5 | Tela de anexos e de auditoria | Pronto |
| 6 | Visualização de relatórios e exportação Excel/PDF | Agregados prontos; exportação pendente |
| 7 | Ajuste fino dos perfis | Policies prontas, falta revisar caso a caso |
| 8 | Testes ponta a ponta, revisão de UX, deploy | — |

## Pontos que ficaram em aberto no plano

- **Fluxo de aprovação**: os estados já existem nos enums. Quando a regra entrar, ela
  restringe quem pode chamar a transição (Policy), sem alterar o modelo de dados.
- **Integração Pix**: a validação de chave hoje é só de formato. Consultar o DICT
  entra como uma camada de Service, sem mexer no schema.
- **Hierarquia de gestor**: `ReembolsoPolicy::view` hoje libera para quem tem
  `reembolsos.ver`. O recorte "só a própria equipe" depende de definir quem é gestor
  de quem, o que o plano ainda não estabelece.
- **Notificações de vencimento**: fora do escopo inicial. O sino que aparece na
  topbar do protótipo foi deliberadamente omitido da implementação enquanto não
  houver backend por trás dele.
- **Gestão de usuários**: a permissão `usuarios.gerenciar` existe e é concedida ao
  Administrador, mas não há controller, rota nem tela. Como o auto-cadastro foi
  removido, hoje só o seeder cria acesso — nenhuma fase do roadmap acima reserva
  espaço para isso, e a decisão está pendente (ver Tarefa 37 do plano de telas).
