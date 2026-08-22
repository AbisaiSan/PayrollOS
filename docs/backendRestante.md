# Backend restante

> Levantamento feito em 22/08/2026, cruzando `plano-original.md`, `ESTADO.md` e o
> código real depois das 40 tarefas de redesign de telas.
>
> **8 tarefas de código restantes**, mais 1 de infraestrutura que depende de
> decisão sua. Duas delas são defeitos, não trabalho novo.

---

## Como este levantamento foi feito

Não confiei só no que o `ESTADO.md` declara. Cruzei três fontes:

1. **`plano-original.md`** — as regras de negócio (Seção 3), o modelo de dados
   (Seção 4) e o plano de execução em 9 fases (Seção 6).
2. **`ESTADO.md`** — o que o projeto declara como pronto.
3. **O código** — migrations, models, services, controllers, policies, rotas,
   comandos agendados e a suíte de testes.

Onde as três concordam, não há tarefa. As tarefas abaixo saíram de onde
divergem — inclusive um caso em que o `ESTADO.md` diz "pronto" e o código não
sustenta (Tarefa 2).

---

## O que já está pronto

Confirmado no código, não só declarado:

| Fase do plano original | Situação |
|---|---|
| 0 — Fundação | Pronta. Laravel 13 + Inertia + Vue 3 + TS + Tailwind + PrimeVue, Breeze sem auto-cadastro, `spatie/laravel-permission` com catálogo em `App\Support\Perfis` e `Permissoes` |
| 1 — Colaboradores e contas/Pix | Pronta. CRUD, contas polimórficas, conta principal única (índice parcial no banco), validação de CPF e de formato de chave Pix |
| 2 — Fornecedores e contratos | Pronta. CRUD PF/PJ, contratos, validação de CPF/CNPJ |
| 3 — Categorias e pagamentos | Pronta. CRUD, geração recorrente agendada às 06:00, transições validadas com `historico_status`, upload de comprovante, filtros |
| 4 — Reembolsos | Pronta no fluxo. **Falta a consolidação em relatórios** — ver Tarefa 2 |
| 5 — Anexos e auditoria | Pronta. Anexos polimórficos com download autenticado, `activitylog` nos seis models |
| 6 — Relatórios e dashboard | Dashboard pronto, agregados prontos. **Exportação pendente** — ver Tarefa 1 |
| 7 — Permissões refinadas | Policies escritas para os oito módulos. **Falta revisar e testar caso a caso**, e há um defeito que as anula — ver Tarefas 3 e 5 |
| 8 — Testes, ajustes e deploy | 75 testes, mas com buracos de cobertura — ver Tarefa 4 |
| 9 — Gestão de usuários | Pronta. Construída na Tarefa 37 do redesign |

Todas as tabelas da Seção 4 do plano original estão migradas, com models, enums,
relacionamentos e o morph map curto (`colaborador`, `fornecedor`, …) no
`AppServiceProvider`. Nada falta no modelo de dados.

---

# As tarefas

Estão na ordem em que recomendo executar: primeiro o que desbloqueia interface já
construída, depois os defeitos, depois cobertura, depois o que exige decisão.

---

## Tarefa 1 — Exportação de relatórios em Excel e PDF

**Origem:** Fase 6 do plano original. É o único item que o `ESTADO.md` já
declarava pendente.

**Situação hoje:** `RelatorioController::exportar` tem duas linhas — um `TODO` e
um `abort(501)`. A rota `relatorios.exportar` existe e responde `501`.

**Por que é a primeira:** é a única pendência de backend que deixa **interface
pronta parada**. A Tarefa 28 do redesign construiu o modal de exportação com o
botão desabilitado justamente esperando isto. Assim que o endpoint existir, ligar
é trocar o botão por um link.

**O que fazer:**

- Classe de exportação Excel usando `maatwebsite/excel` (já instalado, `^4.0`)
- Template Blade + `barryvdh/laravel-dompdf` (já instalado, `^3.1`) para o PDF
- Validar o parâmetro `formato` (`xlsx` ou `pdf`) e recusar o resto
- **Respeitar exatamente os mesmos filtros da tela** — período, categoria e
  status. O modal promete "mantém os filtros aplicados na tela"; um arquivo com
  recorte diferente do que a pessoa está vendo é pior que não exportar
- Permissão `relatorios.exportar` já é checada na rota, manter
- Cabeçalho do arquivo dizendo qual período e quais filtros foram aplicados, pelo
  mesmo motivo
- Ligar o botão no `ModalExportarRelatorio.vue` e remover o aviso "Exportação em
  breve"

**Depende de:** nada.

---

## Tarefa 2 — Reembolsos consolidados nos relatórios

**Origem:** regra 3.7 do plano original, textualmente: *"Aparece de forma
consolidada nos relatórios de pagamento junto com folha e fornecedores."*

**Situação hoje:** o `RelatorioController` consulta **apenas** a tabela
`pagamentos`. A palavra `Reembolso` não aparece no arquivo. Pagar um reembolso
muda o status dele e não cria nenhuma linha em `pagamentos`, então **um reembolso
nunca entra em relatório nenhum**.

**Por que isso importa:** o dashboard mostra "Reembolsos pendentes" como
indicador separado, o que dá a impressão de que os números estão cobertos. Não
estão: o total do período no relatório está subestimado pelo valor de todos os
reembolsos pagos. Quem fecha o mês por esse número fecha errado.

**O que decidir antes de fazer:** existem dois caminhos, e eles não são
equivalentes.

| Caminho | Como funciona | Custo | Efeito colateral |
|---|---|---|---|
| **A. Unir na consulta** | O relatório soma `pagamentos` + `reembolsos` no mesmo agregado | Menor. Só mexe no `RelatorioController` | Reembolso não tem `categoria_id`, tem `categoria` enum própria. A quebra "por categoria" precisa reconciliar dois vocabulários |
| **B. Reembolso pago gera pagamento** | Aprovar/pagar um reembolso cria um lançamento na categoria "Reembolso" | Maior. Mexe no `ReembolsoService` e em dados já existentes | Fica consistente em toda a aplicação, não só no relatório. Mas duplica a informação em dois lugares |

**Minha recomendação: caminho A.** O plano diz "aparece de forma consolidada nos
relatórios", não "vira pagamento". O caminho B muda o significado de um reembolso
no sistema inteiro para resolver um problema que é só de relatório, e o
`ESTADO.md` registra que reembolso e pagamento são fluxos separados de propósito.

**Vou perguntar antes de começar esta tarefa.**

**Depende de:** decisão sua. Convém fazer junto ou logo depois da Tarefa 1, senão
a exportação sai com o mesmo número incompleto e precisará ser refeita.

---

## Tarefa 3 — `Gate::before` do Administrador anula guardas de policy

**Origem:** Fase 7 do plano. É um defeito, não trabalho novo.

**Situação hoje:** `AppServiceProvider.php:46`:

```php
Gate::before(fn ($user) => $user->hasRole(Perfis::ADMINISTRADOR) ? true : null);
```

Isso concede tudo ao Administrador **antes de qualquer policy rodar**. Toda
guarda fina escrita numa policy é inútil justamente para o perfil que mais tem
poder de fazer estrago. Dois casos já apareceram na prática:

1. **`CategoriaPagamentoPolicy::delete`** — a policy bloqueia excluir categoria
   que tem pagamento vinculado, porque isso quebraria o relatório histórico. O
   administrador passa por cima, chega no banco e recebe **erro 500** de violação
   de chave estrangeira. **Continua aberto.**
2. **`UserPolicy::alternarAtivo`** — o administrador conseguia desativar a própria
   conta e, sendo o único, trancaria o sistema para todo mundo. Contornado com um
   `abort_if` explícito no `UsuarioController` durante a Tarefa 37, mas o
   contorno é local: a próxima guarda escrita numa policy vai cair no mesmo buraco.

**O que fazer:**

- Trocar o `Gate::before` cego por um que respeite uma lista de habilidades que
  nem o administrador contorna (as que existem por integridade de dados, não por
  hierarquia de acesso)
- Remover o `abort_if` de contorno do `UsuarioController`, que passa a ser
  redundante
- Fazer o `destroy` de categoria devolver recusa legível em vez de 500
- Teste para cada um dos dois casos

**Depende de:** nada. Independente das Tarefas 1 e 2.

---

## Tarefa 4 — Cobertura de teste dos módulos sem teste

**Origem:** Fase 8, *"testes ponta a ponta dos principais fluxos"*.

**Situação hoje:** 75 testes, concentrados. O que **tem** teste: contas
bancárias (6), contratos recorrentes (7), pagamentos (9), reembolsos (5),
usuários (11), perfil (5), autenticação (16), mais os unitários de CPF/CNPJ e
chave Pix.

O que **não tem nenhum teste**:

| Módulo | O que ficaria coberto |
|---|---|
| Colaboradores | CRUD, unicidade de CPF, desligamento (data não anterior à admissão), filtros da listagem |
| Fornecedores | CRUD, unicidade de documento, validação PF versus PJ |
| Contratos | CRUD, periodicidade obrigatória em recorrente, término não anterior ao início |
| Categorias | CRUD, unicidade de nome, categoria em uso não excluída |
| Anexos | Upload nos três tipos de registro, mimes recusados, download autenticado, remoção |
| Auditoria | Registro do de/para, filtros, quem alterou |
| Relatórios | Agregados respeitando o período e os filtros |

Boa parte disso eu já verifiquei com testes de fumaça durante o redesign, mas
**eles foram removidos** ao fim de cada tarefa, como o protocolo mandava. Esta
tarefa transforma aquela verificação em suíte permanente.

**O que fazer:** um arquivo de teste por módulo da tabela acima, no padrão dos
que já existem.

**Depende de:** convém vir depois das Tarefas 1 a 3, para já cobrir o
comportamento corrigido em vez de testar o que vai mudar.

---

## Tarefa 5 — Matriz de permissões testada por perfil

**Origem:** Fase 7, *"ajuste fino dos perfis de acesso"* — que o `ESTADO.md`
descreve como *"policies prontas, falta revisar caso a caso"*.

**Situação hoje:** as oito policies existem e cada controller as chama. O que não
existe é **prova de que o resultado bate com a intenção**. Hoje ninguém sabe
dizer, sem ler oito arquivos, se o perfil Leitura consegue ou não abrir o
formulário de lançamento.

**O que fazer:**

- Um teste que percorre os quatro perfis contra cada rota da aplicação e afirma
  o que cada um pode e não pode
- Comparar o resultado com a intenção declarada em `Perfis::permissoesPorPerfil()`
  e com a Seção 3.9 do plano original
- Corrigir as divergências que aparecerem

**Por que separado da Tarefa 4:** aquela testa *o que cada módulo faz*, esta testa
*quem pode chamar*. São perguntas diferentes e falham por motivos diferentes.

**Depende de:** Tarefa 3. Testar a matriz antes de consertar o `Gate::before`
registraria o comportamento errado como se fosse o esperado.

---

## Tarefa 6 — Hierarquia de gestor

**Origem:** Seção 3.9 do plano original define Gestor como *"visualiza e solicita
reembolsos da própria equipe"*. Está listado como ponto em aberto no `ESTADO.md`.

**Situação hoje:** `ReembolsoPolicy::view` libera geral para quem tem
`reembolsos.ver`. Não existe nenhuma noção de quem é gestor de quem. A Tarefa 9
do redesign foi instruída explicitamente a **não** construir filtro de "minha
equipe", porque a interface não deve sugerir um recorte que o backend não
sustenta.

**O que decidir antes:** de onde sai a hierarquia.

| Opção | Como | Custo |
|---|---|---|
| Campo `gestor_id` em `colaboradores` | Cada colaborador aponta para o gestor | Migration + formulário + policy |
| Por departamento | Gestor enxerga quem está no mesmo departamento | Sem migration; usa o campo que já existe |
| Manter como está | Gestor continua vendo tudo, e a Seção 3.9 é ajustada | Zero |

**Vou perguntar antes de começar esta tarefa.** É a única que muda o modelo de
dados, e a resposta define se ela é pequena ou média.

**Depende de:** decisão sua. Depois dela, revisar a Tarefa 9 do redesign para
adicionar o filtro de equipe, se a hierarquia passar a existir.

---

## Tarefa 7 — Limpeza de dependência e resto de scaffold

**Origem:** higiene. Não está em nenhuma fase, apareceu na varredura.

**Situação hoje:**

- **`spatie/laravel-medialibrary` está instalado e não é usado.** O plano
  original o sugeria como alternativa para anexos, mas o `AnexoService` usa o
  `Storage` nativo — que é a escolha certa aqui, porque comprovante carrega dado
  bancário e precisa de disco privado com download por rota autenticada. A
  migration `create_media_table` e a config foram publicadas e a tabela está
  vazia. É peso morto que confunde quem chegar depois.
- **`tests/Unit/ExampleTest.php`** ainda é o `assertTrue(true)` que veio do
  Laravel. O `tests/Feature/ExampleTest.php` foi reaproveitado com testes reais
  de rota; o unitário não.

**O que fazer:** remover o pacote, a migration e a config de media library, e
apagar o teste de exemplo unitário. Se preferir manter o pacote para uso futuro,
digo isso no lugar de remover — mas então vale um comentário explicando por que
está lá parado.

**Depende de:** nada. É a menor tarefa da lista.

---

## Tarefa 8 — Documentação de uso e de operação

**Origem:** Fase 8, *"documentação básica de uso"*.

**Situação hoje:** o `README.md` tem 113 linhas e cobre stack, pacotes e setup.
O que não cobre é o que alguém precisa para **operar** o sistema:

- As duas rotinas agendadas (06:00 gera recorrentes, 06:15 promove atrasados) e o
  fato de que sem `schedule:run` no cron **linhas param de aparecer sozinhas** —
  é o tipo de coisa que se descobre tarde
- Que o disco de anexos é privado e o `storage:link` **não** deve ser usado para
  eles
- Como criar o primeiro administrador (seeder), já que não há auto-cadastro
- O que cada perfil enxerga
- Variáveis de ambiente que importam em produção

**Depende de:** conteúdo das Tarefas 1 a 6, então vem depois delas.

---

## Tarefa 9 — Deploy em homologação e produção

**Origem:** Fase 8.

**Não é tarefa de código** e depende de infraestrutura que eu não tenho acesso:
onde hospedar, domínio, banco gerenciado, certificado, cron, backup.

O que **posso** entregar sem esses acessos: checklist de deploy, revisão do
`.env.example`, script de build e verificação de que nada sensível está
versionado. Diga se quer isso como tarefa; se preferir, encerro o backend na
Tarefa 8.

---

## Fora de escopo, por decisão do próprio plano

Não são tarefas. Estão aqui para não voltarem como dúvida:

| Item | Onde está definido |
|---|---|
| Execução real de pagamentos (Pix/TED) | Seção 1 do plano original, fora de escopo |
| Integração bancária, consulta ao DICT, importação de OFX | Seção 1 e Seção 7 |
| Fluxo de aprovação em múltiplos níveis | Seção 7. Os enums já comportam quando entrar |
| Notificações de vencimento por e-mail | Seção 7. Por isso o sino da topbar foi omitido no redesign |
| Colaborador com acesso próprio para pedir reembolso | Seção 7. A estrutura de usuários já comporta |

---

## Resumo

| # | Tarefa | Tipo | Decisão sua antes? |
|---|---|---|---|
| 1 | Exportação Excel e PDF | Funcionalidade | Não |
| 2 | Reembolsos nos relatórios | Defeito de regra | **Sim** |
| 3 | `Gate::before` anula policies | Defeito | Não |
| 4 | Testes dos módulos sem cobertura | Qualidade | Não |
| 5 | Matriz de permissões por perfil | Qualidade | Não |
| 6 | Hierarquia de gestor | Modelo de dados | **Sim** |
| 7 | Limpeza de dependência | Higiene | Não |
| 8 | Documentação de operação | Documentação | Não |
| 9 | Deploy | Infraestrutura | **Sim** |

**8 tarefas de código, 1 de infraestrutura.** Três precisam de uma decisão sua
antes de começarem — vou perguntar quando chegar em cada uma, não agora.

Protocolo, igual ao do redesign: uma tarefa por vez, paro ao terminar, mostro o
que mudou, peço aprovação para commitar e subir, e só então sigo para a próxima.
