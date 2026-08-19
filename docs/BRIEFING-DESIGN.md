# Briefing de Design — PayrollOS

> Documento único e autocontido para redesenhar o frontend do PayrollOS.
> Extraído do código-fonte e do schema PostgreSQL em 19/08/2026.
> Repositório: github.com/AbisaiSan/PayrollOS

---

## PEDIDO

Projete o protótipo visual de **todas as telas do PayrollOS** — as que já existem em código
e as que ainda não foram construídas. São **40 telas e estados** listados na seção 11.

Entregue um sistema visual coeso: uma casca de aplicação, uma gramática de tabela, uma
gramática de formulário e um vocabulário de status que se repitam em todas as telas. Não
trate cada tela como uma peça isolada.

**Três coisas definem o sucesso deste redesign:**

1. **Densidade legível.** É uma ferramenta operada o dia inteiro por um time financeiro, não
   um site institucional. As telas centrais são grids com muitas linhas, números e datas. O
   design precisa aguentar 100 linhas na tela sem cansar, com números alinhados em coluna.

2. **Status legível em relance.** O usuário varre a lista procurando o que está atrasado. O
   estado precisa se ler pela forma, não só pela cor — quem não distingue vermelho de verde
   também precisa achar o atraso.

3. **Nenhuma promessa falsa de execução.** O sistema não move dinheiro. Nenhum botão pode
   sugerir que dispara uma transferência.

Priorize as telas na ordem da seção 11. Se precisar cortar escopo, corte do fim da lista.

---

## 1. O produto

PayrollOS é o módulo interno da Corebanx para **controlar** pagamentos: folha de
colaboradores, fornecedores, prestadores de serviço e reembolsos, com contas bancárias,
chaves Pix e acompanhamento de vencimentos.

### A restrição que molda tudo

**O sistema não executa pagamentos.** Não há integração bancária, não há botão que move
dinheiro. Quando o financeiro marca algo como "Pago", está registrando que *já pagou por
fora*, pelo internet banking.

A interface nunca deve sugerir que a ação dispara uma transferência. O vocabulário certo é
"confirmar", "registrar", "lançar". Nunca "pagar agora", "enviar", "processar".

### Quem usa

Uso interno, uma empresa só. Quatro perfis. A interface esconde ações que o perfil não pode
executar (a autorização real fica no backend; a UI só evita oferecer o que será negado).

| Perfil | Slug | O que faz na tela |
|---|---|---|
| Administrador | `administrador` | Tudo, inclusive gestão de usuários e auditoria |
| Financeiro | `financeiro` | Perfil principal. Lança, edita e confirma pagamentos; aprova reembolsos; exporta relatórios |
| Gestor | `gestor` | Consulta e solicita reembolsos. Não lança pagamento |
| Leitura | `leitura` | Só consulta e relatórios. Nenhuma ação de escrita |

### Densidade esperada

Ferramenta operada diariamente. As telas centrais são **grids pesadas com filtros** —
listagem de pagamentos e de colaboradores. O usuário passa o dia varrendo linhas, comparando
datas e valores. Prioridade para legibilidade de números, alinhamento de colunas e leitura de
status em relance.

---

## 2. Paleta e marca

Quatro cores institucionais da Corebanx. São fixas e devem ser respeitadas.

| Cor | Hex | Uso atual |
|---|---|---|
| Laranja | `#F37B46` | Cor de ação. Botões primários, item ativo do menu, barra de progresso, links de destaque |
| Azul | `#214396` | Cromo da navegação. Fundo da sidebar, avatar do usuário, marcadores de chave Pix |
| Quase-preto | `#0D0E0E` | Tinta. Texto principal e, com opacidade, toda a hierarquia secundária |
| Cinza claro | `#EFEFEE` | Fundo da aplicação. Os cartões ficam em branco puro sobre ele |

### Divisão de trabalho entre as duas cores fortes

Hoje o **laranja é ação** e o **azul é navegação**. Foi deliberado: se o azul também virasse
cor de botão, competiria com os botões nas telas de lançamento, que é onde a atenção precisa
estar. Mantenha essa separação, ou substitua por outra igualmente explícita.

### Escalas derivadas já em uso

Laranja: `50 #FEF3ED` · `100 #FDE2D3` · `200 #FAC4A7` · `300 #F7A57B` · `400 #F5904F` ·
`500 #F37B46` · `600 #DB5F26` · `700 #B14A1D` · `800 #87381A` · `900 #5C2712`

Azul: `50 #EDF1FA` · `100 #D4DCF2` · `200 #A9B9E5` · `300 #7E95D8` · `400 #5372CB` ·
`500 #3457B4` · `600 #214396` · `700 #1A3577` · `800 #132758` · `900 #0C1939`

### Tipografia atual

Figtree como família única, herdada do scaffolding do Laravel Breeze — nunca foi uma decisão
de projeto. **Está aberta a mudança.** O único requisito real é numeral tabular nas grids:
valores e datas precisam alinhar coluna a coluna.

### Cores semânticas de status

Independentes da paleta da marca. Hoje mapeadas nas severidades do PrimeVue (`success`,
`info`, `warn`, `danger`, `secondary`). Pode trocar os valores, mas mantenha os cinco níveis
distinguíveis — inclusive para daltônicos, já que "atrasado" e "pago" não podem depender só
de matiz.

---

## 3. Navegação

Sidebar fixa de 256 px em desktop, drawer sobreposto abaixo de 1024 px. Fundo azul `#214396`,
item ativo em laranja sólido, inativos em branco a 70%.

### Ordem dos itens e por quê

Não é alfabética nem por importância conceitual: **o que se lança todo dia vem antes do que se
cadastra uma vez.**

| # | Item | Rota | Ícone atual | Permissão para aparecer |
|---|---|---|---|---|
| 1 | Dashboard | `dashboard` | `pi-home` | sempre visível |
| 2 | Pagamentos | `pagamentos.index` | `pi-wallet` | `pagamentos.ver` |
| 3 | Reembolsos | `reembolsos.index` | `pi-receipt` | `reembolsos.ver` |
| 4 | Colaboradores | `colaboradores.index` | `pi-users` | `colaboradores.ver` |
| 5 | Fornecedores | `fornecedores.index` | `pi-briefcase` | `fornecedores.ver` |
| 6 | Contratos | `contratos.index` | `pi-file` | `contratos.ver` |
| 7 | Categorias | `categorias.index` | `pi-tags` | `categorias.ver` |
| 8 | Relatórios | `relatorios.index` | `pi-chart-bar` | `relatorios.ver` |
| 9 | Auditoria | `auditoria.index` | `pi-history` | `auditoria.ver` |

### Barra superior

- Altura 64 px, fundo branco, sticky no topo, borda inferior sutil
- Botão hambúrguer (só abaixo de 1024 px) à esquerda
- Slot central: título e descrição da página, com as ações da tela alinhadas à direita
- À direita: avatar circular azul com a inicial do usuário, nome, e menu com "Meu perfil" e "Sair"

### Rodapé da sidebar

Há uma linha permanente em texto miúdo: *"Sistema de controle. Não executa pagamentos."* —
lembrete deliberado da natureza do produto. Preserve a intenção, mesmo que a forma mude.

### Feedback ao usuário

Mensagens de sucesso e erro chegam do backend por sessão flash e viram **toast no canto
superior direito** (4 s para sucesso, 6 s para erro). Confirmações destrutivas usam diálogo
modal.

---

## 4. Rotas

74 rotas no total. Toda rota de aplicação exige autenticação e e-mail verificado.

### Colaboradores

| Verbo | URI | Nome | Tela |
|---|---|---|---|
| GET | `/colaboradores` | `colaboradores.index` | Listagem |
| GET | `/colaboradores/create` | `colaboradores.create` | Formulário novo |
| POST | `/colaboradores` | `colaboradores.store` | — |
| GET | `/colaboradores/{id}` | `colaboradores.show` | Detalhe |
| GET | `/colaboradores/{id}/edit` | `colaboradores.edit` | Formulário edição |
| PUT | `/colaboradores/{id}` | `colaboradores.update` | — |
| POST | `/colaboradores/{id}/desligar` | `colaboradores.desligar` | Modal de desligamento |
| DELETE | `/colaboradores/{id}` | `colaboradores.destroy` | — |

### Pagamentos

| Verbo | URI | Nome | Tela |
|---|---|---|---|
| GET | `/pagamentos` | `pagamentos.index` | Listagem |
| GET | `/pagamentos/create` | `pagamentos.create` | Formulário de lançamento |
| POST | `/pagamentos` | `pagamentos.store` | — |
| GET | `/pagamentos/{id}` | `pagamentos.show` | Detalhe + histórico |
| GET | `/pagamentos/{id}/edit` | `pagamentos.edit` | Formulário edição |
| PUT | `/pagamentos/{id}` | `pagamentos.update` | — |
| POST | `/pagamentos/{id}/status` | `pagamentos.status` | Modal de mudança de status |
| POST | `/pagamentos/{id}/confirmar` | `pagamentos.confirmar` | Modal de confirmação |
| DELETE | `/pagamentos/{id}` | `pagamentos.destroy` | Cancela (não apaga) |

### Fornecedores e contratos

| Verbo | URI | Nome |
|---|---|---|
| GET | `/fornecedores` | `fornecedores.index` |
| GET | `/fornecedores/create` | `fornecedores.create` |
| POST | `/fornecedores` | `fornecedores.store` |
| GET | `/fornecedores/{id}` | `fornecedores.show` |
| GET | `/fornecedores/{id}/edit` | `fornecedores.edit` |
| PUT | `/fornecedores/{id}` | `fornecedores.update` |
| DELETE | `/fornecedores/{id}` | `fornecedores.destroy` |
| GET | `/contratos` | `contratos.index` |
| GET | `/contratos/create` | `contratos.create` |
| POST | `/contratos` | `contratos.store` |
| GET | `/contratos/{id}` | `contratos.show` |
| GET | `/contratos/{id}/edit` | `contratos.edit` |
| PUT | `/contratos/{id}` | `contratos.update` |
| DELETE | `/contratos/{id}` | `contratos.destroy` |

### Contas bancárias e chaves Pix

Aninhadas no beneficiário. `{tipo}` aceita `colaborador` ou `fornecedor` — a tabela é
compartilhada pelos dois, então o tipo precisa vir na URL.

| Verbo | URI | Nome | Ação na tela |
|---|---|---|---|
| POST | `/{tipo}/{id}/contas` | `contas.store` | Salvar do modal |
| PUT | `/{tipo}/{id}/contas/{conta}` | `contas.update` | Editar |
| POST | `/{tipo}/{id}/contas/{conta}/principal` | `contas.principal` | "Tornar principal" |
| POST | `/{tipo}/{id}/contas/{conta}/inativar` | `contas.inativar` | "Inativar" (com confirmação) |
| POST | `/{tipo}/{id}/contas/{conta}/reativar` | `contas.reativar` | "Reativar" |

### Reembolsos, categorias, anexos e o resto

| Verbo | URI | Nome |
|---|---|---|
| GET | `/reembolsos` | `reembolsos.index` |
| GET | `/reembolsos/create` | `reembolsos.create` |
| POST | `/reembolsos` | `reembolsos.store` |
| GET | `/reembolsos/{id}` | `reembolsos.show` |
| GET | `/reembolsos/{id}/edit` | `reembolsos.edit` |
| PUT | `/reembolsos/{id}` | `reembolsos.update` |
| POST | `/reembolsos/{id}/status` | `reembolsos.status` |
| DELETE | `/reembolsos/{id}` | `reembolsos.destroy` |
| GET | `/categorias` | `categorias.index` |
| GET | `/categorias/create` | `categorias.create` |
| POST | `/categorias` | `categorias.store` |
| GET | `/categorias/{id}/edit` | `categorias.edit` |
| PUT | `/categorias/{id}` | `categorias.update` |
| DELETE | `/categorias/{id}` | `categorias.destroy` |
| POST | `/{tipoRegistro}/{id}/anexos` | `anexos.store` |
| GET | `/anexos/{id}/download` | `anexos.download` |
| DELETE | `/anexos/{id}` | `anexos.destroy` |
| GET | `/relatorios` | `relatorios.index` |
| GET | `/relatorios/exportar` | `relatorios.exportar` |
| GET | `/auditoria` | `auditoria.index` |
| GET | `/dashboard` | `dashboard` |
| GET | `/profile` | `profile.edit` |

### Autenticação

`login`, `logout`, `password.request`, `password.email`, `password.reset`, `password.store`,
`password.confirm`, `password.update`, `verification.notice`, `verification.verify`,
`verification.send`.

**Não existe cadastro público.** As rotas de registro foram removidas de propósito. Usuários
são criados pelo administrador. A tela de login **não deve ter link "criar conta"**. A raiz
`/` redireciona direto para `/dashboard`; não há landing page.

---

## 5. Telas construídas e seus dados

Os campos vêm dos controllers, então são o contrato real. O redesign pode reorganizar a
apresentação, mas estes são os dados disponíveis.

### Dashboard — `/dashboard`

Visão do mês corrente. Primeira tela após o login.

**Quatro cartões de indicador:**

| Rótulo | Campo | Formato | Detalhe secundário |
|---|---|---|---|
| A pagar no mês | `indicadores.aPagarNoMes` | moeda BRL | "Pendentes, agendados e atrasados" |
| Pago no mês | `indicadores.pagoNoMes` | moeda BRL | "Confirmado manualmente" |
| Atrasados | `indicadores.atrasados.valor` | moeda BRL | "N lançamento(s) vencido(s)" — cartão em alerta se N > 0 |
| Reembolsos pendentes | `indicadores.reembolsosPendentes.valor` | moeda BRL | "N solicitação(ões)" |

**Tabela "Próximos vencimentos"** — próximos 7 dias, máx. 10 linhas:

| Coluna | Campo | Observação |
|---|---|---|
| Descrição | `descricao` | Link; abaixo, `beneficiario` em texto menor |
| Vencimento | `data_vencimento` | dd/mm/aaaa + legenda relativa: "hoje", "amanhã", "em 3 d", "5 d atrás" |
| Valor | `valor` | moeda, numeral tabular |
| Status | `status` | badge |

**Painel "Em aberto por categoria"** — lista de `{nome, total}`. Cada linha traz nome, valor
em moeda e barra de proporção horizontal (percentual sobre o total).

### Pagamentos, listagem — `/pagamentos`

**A tela mais importante do sistema.** Grid pesada, paginação no servidor.

**Dois totalizadores no topo:** `totais.emAberto` e `totais.atrasado`, ambos em moeda. O de
atrasado entra em alerta quando maior que zero.

**Filtros:**

| Filtro | Controle | Origem das opções |
|---|---|---|
| `busca` | texto livre, debounce 350 ms | busca na descrição |
| `status` | select limpável | 5 status de pagamento |
| `categoria_id` | select limpável | categorias ativas |
| `inicio` | seletor de data | "Vence de" |
| `fim` | seletor de data | "Vence até" |

O backend também aceita `competencia`, `beneficiario_tipo` e `beneficiario_id`, ainda sem
controle na tela.

**Colunas:**

| Coluna | Campo | Conteúdo |
|---|---|---|
| Descrição | `descricao` | Link; abaixo `beneficiario_nome` |
| Categoria | `categoria` | Nome; abaixo `competencia` formatada como "Ago/2026" |
| Vencimento | `data_vencimento` | dd/mm/aaaa |
| Valor | `valor` | moeda, peso médio |
| Forma | `forma_pagamento` | pix · ted · boleto · dinheiro · outro |
| Status | `status` | badge; abaixo `data_pagamento` quando houver |

Também disponível por linha, hoje sem coluna: `conta_destino` (resumo legível, ex. "Itaú,
Ag. 1234, C/C 56789-0") e `beneficiario_tipo`.

> **Sinalização que o redesign precisa manter:** linhas vencidas e ainda não confirmadas
> ganham fundo avermelhado sutil. Isso existe porque a promoção para "Atrasado" só acontece
> na rotina diária das 6h15 — entre o vencimento e a rotina, a linha ainda diz "Pendente" mas
> já está vencida. Sem esse realce, o atraso fica invisível por até um dia.

Paginação: 20 por página, opções de 20 / 50 / 100.

### Colaboradores, listagem — `/colaboradores`

**Filtros:** `busca` (nome, CPF, cargo ou e-mail, com debounce), `status` (select) e
`departamento` (select alimentado pelos departamentos existentes no banco).

**Colunas:**

| Coluna | Campo | Conteúdo |
|---|---|---|
| Nome | `nome` | Link; abaixo o CPF formatado 000.000.000-00 |
| Cargo | `cargo` | Abaixo, `departamento` |
| Contrato | `tipo_contrato` | CLT · PJ · Estágio · Autônomo |
| Admissão | `data_admissao` | dd/mm/aaaa |
| Salário base | `salario_base` | moeda |
| Contas | `contas_bancarias_count` | Número; **fica vermelho quando é zero** — sem conta, não há como lançar folha |
| Status | `status` | badge |

Paginação: 15 por página, opções de 15 / 30 / 50.

### Colaborador, detalhe — `/colaboradores/{id}`

Layout de três colunas: conteúdo principal ocupa duas, o painel de contas ocupa uma.

**Bloco "Dados cadastrais"** — lista de definição com oito pares: CPF (formatado), Cargo,
Departamento, Tipo de contrato, Admissão, Salário base, E-mail, Telefone.

Quando `data_desligamento` existe, aparece aviso em faixa cinza: *"Desligado em dd/mm/aaaa. O
histórico foi preservado e novos lançamentos de folha estão bloqueados."*

**Bloco "Últimos pagamentos"** — até 20. Colunas: Descrição (link), Categoria, Vencimento,
Valor, Status.

**Painel lateral "Contas e chaves Pix"** — ver seção 8.

### Login — `/login`

Ainda no visual padrão do Breeze. Campos: e-mail, senha, "lembrar-me", link "esqueceu a
senha?". Sem link de cadastro.

---

## 6. Telas com backend pronto e interface pendente

Todas existem como arquivo e rota funcional. Os dados abaixo já são entregues pelos
controllers.

| Tela | Rota | Dados que o backend já envia |
|---|---|---|
| Pagamento — detalhe | `pagamentos.show` | Pagamento completo com beneficiário, categoria, contrato, conta, anexos (com quem enviou), histórico de status (com usuário) e `transicoesPermitidas[]` — a lista de status para os quais este pagamento pode mudar agora |
| Pagamento — formulário | `pagamentos.create` | Categorias ativas, formas de pagamento, status inicial permitido (só Pendente ou Agendado) |
| Reembolsos — listagem | `reembolsos.index` | Paginado com colaborador (nome, departamento); filtros de status, categoria, colaborador e período |
| Reembolso — detalhe | `reembolsos.show` | Colaborador, conta, anexos, histórico de status, `transicoesPermitidas[]` |
| Fornecedores — listagem | `fornecedores.index` | Paginado com `contratos_count` e `contas_bancarias_count`; filtros de busca, status e tipo |
| Fornecedor — detalhe | `fornecedores.show` | Contas bancárias, contratos com categoria, últimos 20 pagamentos |
| Contratos — listagem | `contratos.index` | Paginado com fornecedor e categoria; filtros de status, tipo e fornecedor |
| Contrato — detalhe | `contratos.show` | Contrato com fornecedor, categoria, conta, anexos e os últimos 20 pagamentos que ele gerou |
| Categorias | `categorias.index` | Lista completa (sem paginação) com `pagamentos_count`; filtro por tipo |
| Relatórios | `relatorios.index` | `resumo{total,quantidade}`, `porStatus[]{status,rotulo,total,quantidade}`, `porCategoria[]{nome,total}`; filtros de período, categoria e status |
| Auditoria | `auditoria.index` | Paginado: `{log, descricao, registro_tipo, registro_id, usuario, alteracoes, created_at}`; filtros de módulo, usuário e período |

---

## 7. Formulários

Campos, tipos e regras de validação, extraídos dos Form Requests. **●** = obrigatório,
**○** = opcional.

### Colaborador

| | Campo | Rótulo | Controle | Regras |
|---|---|---|---|---|
| ● | `nome` | Nome completo | texto | máx. 255 |
| ● | `cpf` | CPF | texto com máscara 000.000.000-00 | dígito verificador validado; único; gravado só com dígitos |
| ● | `cargo` | Cargo | texto | máx. 255 |
| ● | `departamento` | Departamento | texto | máx. 255 |
| ● | `tipo_contrato` | Tipo de contrato | select | CLT · PJ · Estágio · Autônomo |
| ● | `data_admissao` | Data de admissão | data dd/mm/aaaa | — |
| ○ | `data_desligamento` | Data de desligamento | data | não pode ser anterior à admissão |
| ● | `salario_base` | Salário base | moeda BRL | ≥ 0 |
| ○ | `email` | E-mail | e-mail | — |
| ○ | `telefone` | Telefone | texto | máx. 20 |
| ● | `status` | Status | select | Ativo · Afastado · Desligado |
| ○ | `observacoes` | Observações | área de texto, 3 linhas | máx. 5000 |

Agrupado hoje em dois cartões: **Dados pessoais** (nome, CPF, e-mail, telefone) e **Vínculo**
(cargo, departamento, tipo, status, admissão, salário, observações). Ações no rodapé,
alinhadas à direita: "Cancelar" discreto e "Cadastrar" / "Salvar alterações" primário.

### Conta bancária e chave Pix

Abre em modal a partir do detalhe do colaborador ou fornecedor.

| | Campo | Rótulo | Controle | Regras |
|---|---|---|---|---|
| ● | `banco` | Banco | texto | — |
| ○ | `codigo_banco` | Código | texto curto | máx. 5, ex. "341" |
| ● | `agencia` | Agência | texto | máx. 10 |
| ● | `conta` | Conta | texto | máx. 20 |
| ○ | `digito` | Dígito | texto | máx. 2 |
| ● | `tipo_conta` | Tipo de conta | select | Conta corrente · Conta poupança |
| ● | `titular_nome` | Nome do titular | texto | — |
| ● | `titular_documento` | CPF/CNPJ | texto | valida CPF ou CNPJ |
| ○ | `tipo_chave_pix` | Tipo da chave | select limpável, "Sem Pix" | obrigatório se houver chave |
| ○ | `chave_pix` | Chave Pix | texto | formato validado conforme o tipo |
| ○ | `principal` | Definir como conta principal (destino padrão) | checkbox | — |

**Validação de chave Pix por tipo:** CPF 11 dígitos · CNPJ 14 dígitos · E-mail formato de
e-mail · Telefone precisa do prefixo `+55` seguido de 10 ou 11 dígitos · Aleatória UUID v4.
Só formato — o sistema não consulta o DICT para saber se a chave existe de verdade. A
mensagem de erro deve deixar isso claro sem prometer verificação real.

### Fornecedor

| | Campo | Rótulo | Controle | Regras |
|---|---|---|---|---|
| ● | `tipo_pessoa` | Tipo de pessoa | select | Pessoa Física · Pessoa Jurídica |
| ● | `razao_social` | Razão social / nome | texto | — |
| ○ | `nome_fantasia` | Nome fantasia | texto | — |
| ● | `documento` | CPF/CNPJ | texto com máscara | valida conforme o tipo de pessoa escolhido acima; único |
| ● | `tipo_fornecedor` | Tipo de fornecedor | select | Fornecedor de produto · Prestador de serviço · Ambos |
| ○ | `email` | E-mail | e-mail | — |
| ○ | `telefone` | Telefone | texto | — |
| ○ | `endereco` | Endereço | texto | máx. 255 |
| ● | `status` | Status | select | Ativo · Inativo |
| ○ | `observacoes` | Observações | área de texto | — |

A máscara do documento muda conforme o tipo de pessoa. É o principal comportamento dinâmico
deste formulário.

### Lançamento de pagamento

| | Campo | Rótulo | Controle | Regras |
|---|---|---|---|---|
| ● | `payable_type` | Tipo de beneficiário | alternador | colaborador ou fornecedor |
| ● | `payable_id` | Beneficiário | busca com autocompletar | só ativos |
| ● | `categoria_id` | Categoria | select | categorias ativas |
| ○ | `contrato_id` | Contrato | select | preenchido quando gerado por contrato |
| ○ | `conta_bancaria_id` | Conta de destino | select | **obrigatório para Pix e TED**; dispensável em boleto e dinheiro |
| ○ | `competencia` | Competência | seletor de mês | formato AAAA-MM, ex. 2026-08 |
| ● | `descricao` | Descrição | texto | máx. 255 |
| ● | `valor` | Valor | moeda BRL | mínimo R$ 0,01 |
| ● | `data_vencimento` | Data de vencimento | data | — |
| ● | `forma_pagamento` | Forma de pagamento | select | Pix · TED · Boleto · Dinheiro · Outro |
| ○ | `status` | Status inicial | select | **só Pendente ou Agendado** — nada nasce pago |
| ○ | `observacoes` | Observações | área de texto | — |

> **Dependências entre campos.** Escolher o tipo de beneficiário filtra a busca do
> beneficiário. Escolher o beneficiário filtra as contas de destino — **só as contas dele, e
> só as ativas**. Escolher Pix ou TED torna a conta obrigatória. Esses três encadeamentos
> precisam sobreviver ao redesign; são a diferença entre lançar certo e mandar dinheiro para a
> conta errada.

### Solicitação de reembolso

| | Campo | Rótulo | Controle | Regras |
|---|---|---|---|---|
| ● | `colaborador_id` | Colaborador | select | só ativos |
| ○ | `conta_bancaria_id` | Conta de destino | select | — |
| ● | `descricao` | Descrição | texto | máx. 255 |
| ● | `categoria` | Categoria da despesa | select | Viagem · Alimentação · Material · Transporte · Outro |
| ● | `valor` | Valor | moeda | mínimo R$ 0,01 |
| ● | `data_solicitacao` | Data da solicitação | data | não pode ser futura |
| ○ | `comprovante` | Comprovante | upload de arquivo | pdf, jpg, jpeg, png, xml · máx. 10 MB |
| ○ | `observacoes` | Observações | área de texto | — |

### Contrato

| | Campo | Rótulo | Controle | Regras |
|---|---|---|---|---|
| ● | `fornecedor_id` | Fornecedor | select | só ativos |
| ○ | `categoria_id` | Categoria | select | — |
| ○ | `conta_bancaria_id` | Conta de destino | select | se vazia, usa a principal do fornecedor |
| ● | `descricao` | Descrição | texto | ex. "Contabilidade", "Aluguel" |
| ● | `tipo` | Tipo | select | Pontual · Recorrente |
| ● | `valor` | Valor | moeda | mínimo R$ 0,01 |
| ○ | `periodicidade` | Periodicidade | select | **obrigatório se recorrente** — Mensal · Quinzenal · Anual |
| ○ | `dia_vencimento` | Dia de vencimento | número 1–31 | **obrigatório se recorrente** |
| ● | `data_inicio` | Data de início | data | — |
| ○ | `data_fim` | Data de término | data | não pode ser anterior ao início |
| ● | `status` | Status | select | Ativo · Suspenso · Encerrado |
| ○ | `observacoes` | Observações | área de texto | — |

Escolher "Recorrente" revela periodicidade e dia de vencimento, que passam a ser
obrigatórios. São eles que alimentam a rotina de geração automática.

### Categoria de pagamento

`nome` ● (único), `tipo` ● (select com os 8 tipos), `descricao` ○, `ativo` ○ (checkbox).

### Desligamento de colaborador

Ação em modal a partir do detalhe: `data_desligamento` ● (não pode ser anterior à admissão) e
`observacoes` ○. Após confirmar, mensagem: *"Colaborador desligado. O histórico de pagamentos
foi mantido; lance a rescisão como pagamento avulso, se houver."*

---

## 8. Vocabulário de estados

Todos os valores possíveis, com o rótulo exato em português que a interface exibe. Mantenha a
grafia.

### Status de pagamento

| Valor | Rótulo | Severidade | Significado |
|---|---|---|---|
| `pendente` | Pendente | warn | Lançado, aguardando a data |
| `agendado` | Agendado | info | O usuário marcou intenção de pagar em data futura |
| `pago` | Pago | success | Confirmado manualmente, com data preenchida |
| `atrasado` | Atrasado | danger | Venceu sem confirmação. Atribuído pela rotina diária |
| `cancelado` | Cancelado | secondary | Estado terminal |

**Transições permitidas** — a tela só deve oferecer estas mudanças. O backend recusa o resto,
e o campo `transicoesPermitidas` já chega pronto na tela de detalhe.

| De | Pode ir para |
|---|---|
| Pendente | Agendado · Pago · Atrasado · Cancelado |
| Agendado | Pendente · Pago · Atrasado · Cancelado |
| Atrasado | Agendado · Pago · Cancelado |
| Pago | Cancelado · Pendente *(reverter confirmação feita por engano)* |
| Cancelado | nenhuma — estado terminal |

### Status de reembolso

| Valor | Rótulo | Pode ir para |
|---|---|---|
| `pendente` | Pendente | Aprovado · Rejeitado |
| `aprovado` | Aprovado | Pago · Rejeitado · Pendente |
| `pago` | Pago | Aprovado |
| `rejeitado` | Rejeitado | Pendente |

Não existe atalho de Pendente direto para Pago: é preciso aprovar antes. Rejeitar exige
motivo, que vai para o histórico.

### Demais enums

**Tipo de contratação:** `clt` CLT · `pj` PJ · `estagio` Estágio · `autonomo` Autônomo

**Status de colaborador:** `ativo` Ativo · `afastado` Afastado · `desligado` Desligado

**Tipo de pessoa:** `pf` Pessoa Física · `pj` Pessoa Jurídica

**Tipo de fornecedor:** `produto` Fornecedor de produto · `servico` Prestador de serviço ·
`ambos` Ambos

**Status de fornecedor:** `ativo` Ativo · `inativo` Inativo

**Tipo de conta:** `corrente` Conta corrente · `poupanca` Conta poupança

**Tipo de chave Pix:** `cpf` CPF · `cnpj` CNPJ · `email` E-mail · `telefone` Telefone ·
`aleatoria` Chave aleatória

**Status de conta bancária:** `ativa` Ativa · `inativa` Inativa

**Tipo de contrato:** `pontual` Pontual · `recorrente` Recorrente

**Periodicidade:** `mensal` Mensal · `quinzenal` Quinzenal · `anual` Anual

**Status de contrato:** `ativo` Ativo · `suspenso` Suspenso · `encerrado` Encerrado

**Forma de pagamento:** `pix` Pix · `ted` TED · `boleto` Boleto · `dinheiro` Dinheiro ·
`outro` Outro

**Categoria de reembolso:** `viagem` Viagem · `alimentacao` Alimentação · `material` Material ·
`transporte` Transporte · `outro` Outro

**Tipos de categoria de pagamento:** `salario` Salário · `ferias` Férias · `decimo_terceiro`
Décimo terceiro · `rescisao` Rescisão · `fornecedor` Fornecedor · `servico` Serviço ·
`reembolso` Reembolso · `outro` Outro

**As 12 categorias já cadastradas:** Salário, Adiantamento salarial, Férias, Décimo terceiro,
Rescisão, Fornecedor, Prestação de serviço, Aluguel, Licença de software, Contabilidade,
Reembolso, Outros.

### Formatação de dados

| Tipo | Formato exibido | Exemplo |
|---|---|---|
| Moeda | BRL, pt-BR | R$ 5.000,00 |
| Data | dd/mm/aaaa | 19/08/2026 |
| Data e hora | dd/mm/aaaa hh:mm | 19/08/2026 14:30 |
| Competência | mês abreviado / ano | Ago/2026 |
| CPF | 000.000.000-00 | 529.982.247-25 |
| CNPJ | 00.000.000/0000-00 | 11.222.333/0001-81 |
| Vazio | travessão | — |

---

## 9. Componentes

### Próprios do projeto

| Componente | O que faz |
|---|---|
| `AuthenticatedLayout` | Casca da aplicação: sidebar, barra superior, toasts, diálogo de confirmação |
| `GuestLayout` | Casca das telas de autenticação |
| `LogoCorebanx` | Marca em SVG, com variante compacta para a sidebar recolhida |
| `CabecalhoPagina` | Título, descrição e slot de ações — vai no slot `header` do layout |
| `StatusBadge` | Badge de status; mapeia 15 valores para 5 severidades e traduz o rótulo |
| `CardIndicador` | Cartão de número do dashboard, com rótulo, valor, detalhe, ícone e estado de alerta |
| `ContasBancarias` | Painel completo de contas e Pix, reaproveitado entre colaborador e fornecedor |
| `EmConstrucao` | Marcador das telas ainda não desenhadas |

### Detalhe do painel ContasBancarias

Cada conta na lista mostra:

- Nome do banco em destaque
- `Ag. 1234 · C/C 56789-0` — tipo abreviado como "C/C" ou "Poup."
- Nome do titular e documento formatado
- Chave Pix, quando houver, precedida de um ícone de raio, em azul
- Selo "Principal" em laranja claro, ou badge "Inativa"
- Ações contextuais: "Tornar principal" (só se ativa e não principal), "Inativar" ou "Reativar"

Estado vazio com texto que explica a consequência: *"Nenhuma conta cadastrada. Sem conta
principal, o lançamento fica sem destino padrão."*

### Biblioteca atual: PrimeVue 4

Em uso hoje: `DataTable`, `Column`, `InputText`, `InputNumber`, `Select`, `DatePicker`,
`Textarea`, `Checkbox`, `Button`, `Dialog`, `Message`, `Tag`, `Toast`, `ConfirmDialog`,
`Menu`. Ícones da fonte PrimeIcons, traduzidos para pt-BR.

**PrimeVue não é compromisso irreversível.** Foi escolhido pelo DataTable pronto com
paginação no servidor, que poupa trabalho nas grids. Se o redesign pedir outra biblioteca ou
componentes próprios, o backend não muda. O que importa preservar é a paginação no servidor:
as listagens podem crescer bastante e não devem carregar tudo de uma vez.

---

## 10. Regras que a UI respeita

Comportamentos que existem por um motivo. Se o redesign mudar a forma, mantenha a intenção.

### Conta principal

- Cada beneficiário tem **no máximo uma** conta principal — garantido no banco, não só na tela
- A primeira conta cadastrada vira principal automaticamente
- Não dá para simplesmente desmarcar "principal": para trocar, marca-se outra conta
- Contas são **inativadas, nunca excluídas** — os pagamentos antigos continuam apontando para elas
- Não é possível inativar a conta principal se houver outra ativa; primeiro define-se a substituta

### Beneficiários bloqueados

Colaborador desligado e fornecedor inativo não aceitam novos lançamentos, mas o histórico
permanece visível. A interface deve deixar claro *por que* a ação não está disponível, em vez
de só esconder o botão.

### Nada nasce pago

Um lançamento novo só pode ser Pendente ou Agendado. Confirmar exige data de pagamento, que
não pode ser futura. E confirmar é permissão separada de lançar — quem registra não precisa
ser quem confirma.

### Tudo fica registrado

Toda mudança de status grava quem alterou, de qual status para qual, quando e uma observação
opcional. As telas de detalhe de pagamento e reembolso devem mostrar essa linha do tempo — é o
que o financeiro consulta quando algo dá errado.

### Automações que aparecem sozinhas

- **06:00** — contratos recorrentes geram seus lançamentos, alguns dias antes do vencimento (5 por padrão), sempre como Pendente
- **06:15** — o que venceu sem confirmação passa para Atrasado

Ou seja: **linhas aparecem e mudam de status sem ninguém clicar.** Vale a interface distinguir
o que foi gerado automaticamente do que foi lançado à mão — hoje isso só está na observação do
histórico.

### Comprovantes

Carregam dados bancários, então nunca ficam em disco público. O download passa por rota
autenticada. Nada de link direto para arquivo.

---

## 11. Inventário de telas a prototipar

**40 telas e estados.** Ordenados por prioridade. As marcadas *(nova)* não existem em código
nenhum — precisam ser inventadas.

### Prioridade 1 — o núcleo operacional

| # | Tela | Situação | Observação |
|---|---|---|---|
| 1 | Casca da aplicação — sidebar expandida | existe | Base de tudo. Definir grid, densidade e comportamento do menu |
| 2 | Casca — drawer mobile | existe | Abaixo de 1024 px |
| 3 | Pagamentos — listagem | existe | A tela mais usada. Grid, filtros, totalizadores, realce de vencido |
| 4 | Pagamento — detalhe | pendente | Dados, linha do tempo de status, anexos, ações |
| 5 | Pagamento — modal de confirmação | pendente | Data de pagamento + observação. Vocabulário sem promessa de execução |
| 6 | Pagamento — modal de mudança de status | pendente | Só as transições permitidas |
| 7 | Pagamento — formulário | pendente | Três encadeamentos entre campos; maior risco operacional |
| 8 | Dashboard | existe | Quatro indicadores, próximos vencimentos, quebra por categoria |

### Prioridade 2 — reembolsos e cadastros

| # | Tela | Situação | Observação |
|---|---|---|---|
| 9 | Reembolsos — listagem | pendente | Filtros por status, categoria, colaborador, período |
| 10 | Reembolso — detalhe | pendente | Comprovante em destaque, linha do tempo, ações de aprovar/rejeitar |
| 11 | Reembolso — formulário | pendente | Com upload de comprovante |
| 12 | Reembolso — modal de rejeição | pendente | Exige motivo |
| 13 | Colaboradores — listagem | existe | |
| 14 | Colaborador — detalhe | existe | Três colunas, com painel de contas |
| 15 | Colaborador — formulário | existe | Dois cartões: dados pessoais e vínculo |
| 16 | Colaborador — modal de desligamento | pendente | Data + observação, com aviso sobre o histórico |
| 17 | Painel de contas bancárias | existe | Componente embutido, reaproveitado em 2 telas |
| 18 | Modal de conta bancária/Pix | existe | 11 campos, com validação por tipo de chave |
| 19 | Fornecedores — listagem | pendente | |
| 20 | Fornecedor — detalhe | pendente | Espelha colaborador, com contratos |
| 21 | Fornecedor — formulário | pendente | Máscara dinâmica PF/PJ |

### Prioridade 3 — contratos, categorias, relatórios

| # | Tela | Situação | Observação |
|---|---|---|---|
| 22 | Contratos — listagem | pendente | |
| 23 | Contrato — detalhe | pendente | Com os pagamentos que ele gerou e o próximo vencimento |
| 24 | Contrato — formulário | pendente | Campos condicionais ao tipo recorrente |
| 25 | Categorias — listagem | pendente | Tabela curta, sem paginação |
| 26 | Categoria — formulário | pendente | 4 campos. Cabe em modal |
| 27 | Relatórios | pendente | Filtros, resumo, quebras por status e categoria, exportar |
| 28 | Relatórios — modal de exportação | *(nova)* | Escolher formato Excel ou PDF |
| 29 | Auditoria | pendente | Lista longa de eventos com diferenças de campos |
| 30 | Componente de anexos | pendente | Upload, lista, download, remover |

### Prioridade 4 — acesso e sistema

| # | Tela | Situação | Observação |
|---|---|---|---|
| 31 | Login | existe | Padrão Breeze. Primeira impressão do sistema |
| 32 | Esqueci a senha | existe | Padrão Breeze |
| 33 | Redefinir senha | existe | Padrão Breeze |
| 34 | Confirmar senha | existe | Padrão Breeze |
| 35 | Verificar e-mail | existe | Padrão Breeze |
| 36 | Meu perfil | existe | Padrão Breeze: dados, senha, excluir conta |
| 37 | **Usuários — listagem e formulário** | *(nova)* | **Ver alerta abaixo** |
| 38 | Erro 403 — sem permissão | *(nova)* | Perfil sem acesso ao módulo |
| 39 | Erro 404 | *(nova)* | |
| 40 | Estados vazios do primeiro uso | *(nova)* | Sistema recém-instalado: sem colaboradores, sem pagamentos, sem nada |

> **Lacuna encontrada no produto.** A permissão `usuarios.gerenciar` existe no catálogo e é
> concedida ao Administrador, mas **não há controller, rota nem tela de usuários**. Hoje o
> único jeito de criar acesso é rodar o seeder. Como o sistema não tem cadastro público, isso
> significa que o administrador não consegue dar acesso a ninguém pela interface. A tela
> precisa existir: listagem de usuários, formulário com nome, e-mail, perfil e vínculo
> opcional a um colaborador, e ação de ativar/desativar.

### Estados que toda listagem precisa

Vale definir uma vez e reaplicar: **carregando**, **vazio por filtro** (com ação de limpar
filtros), **vazio de verdade** (primeiro uso, com chamada para cadastrar) e **erro de
carregamento**.

---

## 12. Pontos ainda em aberto no produto

- **Fluxo de aprovação de pagamento** não existe hoje, mas os estados foram desenhados para acomodá-lo. Pode surgir um passo a mais entre lançar e pagar
- **Notificações de vencimento** estão fora do escopo inicial, mas são adição provável — vale reservar lugar para um indicador na barra superior
- **Acesso do próprio colaborador** para solicitar reembolso está previsto na estrutura de usuários. Se ativar, entra um perfil com visão bem mais restrita
- **Validação real de chave Pix** via DICT pode entrar depois. Hoje é só formato

---

*Extraído do código-fonte e do schema PostgreSQL em 19/08/2026.*
*Stack: Laravel 13 · Vue 3 · Inertia · TypeScript · PrimeVue 4 · Tailwind 3 · PostgreSQL 15.*
