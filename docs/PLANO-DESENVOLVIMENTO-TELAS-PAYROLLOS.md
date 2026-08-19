# Plano de desenvolvimento, redesign de telas do PayrollOS

> Guia de execução para o Claude Code. Cada tarefa corresponde a uma tela do
> inventário da Seção 11 do `BRIEFING-DESIGN.md`. Mantém PrimeVue 4,
> restilizando com os novos tokens (paleta, tipografia, StatusBadge). O
> backend já existe e não deve ser alterado, exceto onde marcado
> explicitamente (Tarefas 28 e 37).
>
> Referências que devem estar no repositório antes de começar:
> `BRIEFING-DESIGN.md` (contrato de dados, rotas, regras),
> `prototipo.html` (referência visual e de interação, não é código a copiar)
> e `ESTADO.md` (estado real da implementação, é a fonte mais atual sobre o
> que já está pronto no backend, prevalece sobre o `BRIEFING-DESIGN.md` em
> caso de divergência, ver seção "Ajustes depois de cruzar com ESTADO.md"
> abaixo). O número de cada tarefa abaixo corresponde ao número da mesma
> tela no índice do protótipo (barra superior do `prototipo.html`), então
> dá pra abrir os três lado a lado.

---

## Protocolo obrigatório, vale para todas as 40 tarefas

Isso não se repete tarefa a tarefa no texto abaixo, mas se aplica a todas,
sem exceção:

1. Implemente **somente** a tarefa da vez. Não adiante trabalho de tarefas
   futuras, mesmo que pareça eficiente.
2. Ao terminar a implementação, **pare**. Não commite nada ainda.
3. Mostre um resumo do que foi feito (arquivos alterados/criados) e
   pergunte: *"Posso commitar e subir essa alteração para o repositório?"*
4. Só commite e faça push depois da minha aprovação explícita. Use uma
   mensagem de commit clara, referenciando o número e o nome da tarefa
   (ex.: `feat(ui): redesign tela de pagamentos - listagem (tarefa 3)`).
5. Depois do push, pergunte: *"Posso seguir para a próxima tarefa?"* e
   espere minha resposta antes de continuar.
6. Nunca pule uma tarefa nem mude a ordem sem eu pedir.

Se em algum momento faltar informação (um campo que o backend não entrega,
uma rota que não existe), pare e pergunte, em vez de inventar.

## Convenções

- Os nomes de arquivo Vue não estão fixados neste documento. Localize o
  arquivo correspondente a cada rota (nomeada no `web.php` ou equivalente)
  antes de editar, em vez de assumir um caminho.
- "Restilizar" significa reaproveitar o componente PrimeVue existente
  (`DataTable`, `Dialog`, `Select` etc.) e ajustar tema, cores, tipografia e
  densidade, não trocar a biblioteca.
- Toda tela nova precisa herdar de `AuthenticatedLayout` (ou `GuestLayout`,
  nas telas de autenticação) e usar `CabecalhoPagina` no slot `header`.
- Números tabulares (valores, datas, IDs) usam a fonte monoespaçada
  definida na Tarefa 1, em toda tela, sem exceção.

---

## Ajustes depois de cruzar com ESTADO.md

O `ESTADO.md` confirma que o backend está pronto para praticamente tudo, o
que valida a maior parte deste plano sem mudança. Quatro pontos, porém,
divergem do que o `BRIEFING-DESIGN.md` sozinho sugeria, e já foram
refletidos nas tarefas abaixo:

1. **Exportação de relatórios (Tarefa 28) não está pronta.** O
   `BRIEFING-DESIGN.md` listava a rota `relatorios.exportar` como existente
   (Seção 4), mas o `ESTADO.md` marca a Fase 6 como "Agregados prontos,
   exportação pendente". Os dados agregados da Tarefa 27 podem seguir
   normalmente, mas a Tarefa 28 depende de um endpoint que ainda não existe
   de verdade. Ajustado para virar uma confirmação obrigatória antes de
   começar, em vez de uma tarefa de restyle simples.
2. **Usuários (Tarefa 37) não aparece em nenhuma fase do roadmap atual.**
   Além de não ter controller/rota/tela (como já dizia o briefing), o
   `ESTADO.md` reforça isso: a tabela de "Próximas fases" (2 a 8) não
   reserva nenhuma fase pra essa tela. Ou seja, ninguém decidiu ainda
   quando o backend dela entra. Mantida a exigência de confirmação antes de
   começar.
3. **Hierarquia de gestor ainda não existe.** O `ESTADO.md` repete o ponto
   em aberto do briefing: `ReembolsoPolicy::view` hoje libera geral para
   quem tem `reembolsos.ver`, o recorte "só a própria equipe" depende de
   uma hierarquia que ainda não foi definida. Isso significa que a Tarefa 9
   (Reembolsos, listagem) **não deve** incluir um filtro de "minha equipe"
   ou qualquer UI que sugira esse recorte, porque o backend não sustenta
   isso ainda. Adicionado como nota na Tarefa 9.
4. **Ajuste fino de perfis (Fase 7 do `ESTADO.md`) ainda não terminou.**
   "Policies prontas, falta revisar caso a caso". Isso afeta toda a
   condicional de "esconder ação que o perfil não pode executar" espalhada
   pelas Tarefas 1, 4, 6, 9 a 12 e 37, o comportamento pode mudar depois
   que a Fase 7 for concluída no backend. Não é motivo pra parar nada
   agora, mas vale revisar essas telas de novo quando a Fase 7 fechar, em
   vez de considerar esse ponto definitivamente encerrado.

Nenhuma divergência de nome de tabela, campo ou rota foi encontrada entre
os dois documentos. Um ponto de schema do `ESTADO.md` vale registrar, sem
exigir mudança de código no frontend: as colunas de estado (status de
pagamento, reembolso etc.) são `string` no banco, não `ENUM` nativo, para
permitir adicionar um status novo (por exemplo, quando o fluxo de
aprovação entrar) sem migração de schema. Na prática, isso reforça a
recomendação já presente na Tarefa 1: o mapa de severidades do
`StatusBadge` deve ter um retorno padrão (severidade neutra) para valores
que ele ainda não conhece, em vez de assumir que a lista de status é
fechada para sempre.

---

## Prioridade 1, núcleo operacional

### Tarefa 1, Casca da aplicação (sidebar expandida)
**Protótipo #1.** Rota: aplica-se a toda rota autenticada.
- Estender `tailwind.config` com os tokens de marca (laranja, azul, quase
  preto, cinza claro, e as escalas derivadas já listadas na Seção 2 do
  briefing) e os 5 tons semânticos de status (sucesso, info, atenção,
  perigo, neutro), com contraste suficiente para leitura por daltônicos.
- Configurar a fonte monoespaçada usada em toda a grid (valores, datas,
  IDs).
- Restilizar `AuthenticatedLayout`: sidebar fixa 256px, fundo azul
  `#214396`, item ativo em laranja sólido, itens inativos em branco a 70%.
  Ordem dos itens exatamente como a tabela da Seção 3 (Dashboard,
  Pagamentos, Reembolsos, Colaboradores, Fornecedores, Contratos,
  Categorias, Relatórios, Auditoria), cada item só aparece se a permissão
  correspondente existir. Os slugs de perfil e permissão vêm dos catálogos
  centralizados em `App\Support\Perfis` e `App\Support\Permissoes`, não
  hardcodar strings soltas na Vue.
- Topbar 64px, branca, sticky, borda inferior sutil, slot central para
  título e ações da tela, avatar do usuário à direita com menu (Meu perfil
  / Sair).
- Rodapé fixo da sidebar com o texto "Sistema de controle. Não executa
  pagamentos.".
- Restilizar `Toast` (sucesso 4s, erro 6s, canto superior direito) e
  `ConfirmDialog` (confirmações destrutivas).
- Criar (ou restilizar, se já existir) o componente `StatusBadge`: recebe
  um valor de status e mapeia para uma das 5 severidades, com ícone
  próprio por severidade (não só cor) e rótulo traduzido. Comece cobrindo
  os status de pagamento; os outros vocabulários entram nas tarefas 9, 13,
  19, 22. Como o banco guarda o status como `string` (não `ENUM` nativo,
  justamente para permitir novos valores sem migração, ver "Ajustes depois
  de cruzar com ESTADO.md"), o mapa de severidades precisa de um retorno
  padrão pra valor desconhecido, em vez de quebrar se aparecer um status
  novo (por exemplo, quando o fluxo de aprovação entrar).
- Criar `CardIndicador` (rótulo, valor em fonte monoespaçada, detalhe
  secundário, ícone, estado de alerta opcional).

### Tarefa 2, Casca (drawer mobile)
**Protótipo #2.** Abaixo de 1024px: sidebar vira drawer sobreposto
(`translateX`), com scrim de fundo, botão hambúrguer aparece na topbar à
esquerda, ações da topbar perdem o texto e ficam só com ícone. Reaproveita
o mesmo componente da Tarefa 1, não duplicar sidebar.

### Tarefa 3, Pagamentos, listagem
**Protótipo #3.** Rota `pagamentos.index`.
- Dois totalizadores no topo: `totais.emAberto` e `totais.atrasado` (o
  segundo em alerta quando maior que zero).
- Filtros: `busca` (texto, debounce 350ms), `status` (Select limpável),
  `categoria_id` (Select limpável, categorias ativas), `inicio` e `fim`
  (DatePicker, "Vence de" / "Vence até"). O backend também aceita
  `competencia`, `beneficiario_tipo` e `beneficiario_id`, sem controle na
  tela hoje, pode adicionar se fizer sentido no layout.
- Colunas: Descrição (+ beneficiário abaixo), Categoria (+ competência
  formatada "Ago/2026"), Vencimento, Valor (peso médio, mono), Forma,
  Status (+ data de pagamento abaixo, quando houver).
- **Regra a preservar:** linhas vencidas e ainda não confirmadas ganham
  fundo avermelhado sutil, mesmo com status ainda "Pendente" (a promoção
  pra "Atrasado" só acontece na rotina das 6h15). Sem isso, o atraso fica
  invisível por até um dia.
- `DataTable` com paginação no servidor, 20 por página, opções 20/50/100.

### Tarefa 4, Pagamento, detalhe
**Protótipo #4.** Rota `pagamentos.show`. O controller já entrega o
pagamento completo com beneficiário, categoria, contrato, conta, anexos
(com quem enviou), histórico de status (com usuário) e
`transicoesPermitidas[]`.
- Bloco de dados principais, mais ações (Confirmar, Mudar status, Editar,
  Cancelar) só visíveis conforme `transicoesPermitidas[]` e permissão do
  perfil.
- Linha do tempo de status: de/para, usuário, quando, observação opcional.
  Distinguir visualmente mudanças automáticas (rotina diária) das
  manuais, hoje isso só está no texto da observação, vale um indicador
  visual próprio.
- **Construa a linha do tempo como componente reutilizável** (algo como
  `HistoricoStatusTimeline`), não amarrado a pagamento. A tabela
  `historico_status` no backend já é polimórfica e compartilhada entre
  pagamentos e reembolsos (ver `ESTADO.md`), então o mesmo componente deve
  servir a Tarefa 10 sem duplicar código.
- Lista de anexos (usa o componente da Tarefa 30, se já existir; senão,
  versão simples aqui e evolui depois).

### Tarefa 5, Pagamento, modal de confirmação
**Protótipo #5.** Rota `pagamentos.confirmar`. `Dialog` com campo de data
de pagamento (não pode ser futura) e observação opcional.
**Vocabulário:** "Confirmar pagamento" / "Registrar como pago". Nunca
"Pagar agora", "Enviar" ou "Processar". O texto de apoio deve deixar claro
que isso registra um pagamento já feito por fora, não dispara nada.

### Tarefa 6, Pagamento, modal de mudança de status
**Protótipo #6.** Rota `pagamentos.status`. `Dialog` que lista **somente**
as opções de `transicoesPermitidas[]` recebidas do backend (não
hardcodar a tabela da Seção 8, ela é só referência, a fonte de verdade é o
campo que a tela de detalhe já recebe). Campo de observação opcional.

### Tarefa 7, Pagamento, formulário
**Protótipo #7.** Rotas `pagamentos.create` / `store`, `edit` / `update`.
Campos (Seção 7 do briefing): tipo de beneficiário (alternador), 
beneficiário (busca com autocomplete, só ativos), categoria, contrato
(opcional, preenchido quando vem de contrato), conta de destino
(obrigatória para Pix e TED), competência, descrição, valor, data de
vencimento, forma de pagamento, status inicial (só Pendente ou Agendado),
observações.
- **Maior risco operacional da tela, teste com atenção:** três
  encadeamentos precisam sobreviver ao redesign. (1) escolher o tipo de
  beneficiário filtra a busca do beneficiário; (2) escolher o beneficiário
  filtra as contas de destino, só as dele e só as ativas; (3) escolher Pix
  ou TED torna a conta obrigatória.
- `pagamentos.create` já entrega categorias ativas, formas de pagamento e
  o status inicial permitido, não recalcular isso no frontend.

### Tarefa 8, Dashboard
**Protótipo #8.** Rota `dashboard`. Quatro `CardIndicador` (A pagar no
mês, Pago no mês, Atrasados em alerta se N>0, Reembolsos pendentes),
tabela "Próximos vencimentos" (7 dias, máx. 10 linhas, com legenda
relativa "hoje" / "amanhã" / "em N d" / "N d atrás"), painel "Em aberto
por categoria" (nome, valor, barra de proporção horizontal).

---

## Prioridade 2, reembolsos e cadastros

### Tarefa 9, Reembolsos, listagem
**Protótipo #9.** Rota `reembolsos.index`, já paginado com colaborador
(nome, departamento). Filtros de status, categoria, colaborador e período
já existem no backend. Estenda `StatusBadge` para cobrir o vocabulário de
reembolso (Pendente, Aprovado, Pago, Rejeitado).
- **Não construa filtro de "minha equipe" nem qualquer UI de recorte por
  hierarquia de gestor.** O `ESTADO.md` confirma que essa hierarquia ainda
  não foi definida no backend, `reembolsos.ver` hoje libera visão geral.
  Mostre exatamente o que a listagem devolver, sem inventar escopo que o
  backend não sustenta.

### Tarefa 10, Reembolso, detalhe
**Protótipo #10.** Rota `reembolsos.show`, já entrega colaborador, conta,
anexos, histórico de status e `transicoesPermitidas[]`. Comprovante em
destaque (não escondido numa lista genérica). Ações de aprovar/rejeitar
condicionadas às transições permitidas. Reaproveite aqui o componente
`HistoricoStatusTimeline` criado na Tarefa 4, não recrie a linha do
tempo do zero.

### Tarefa 11, Reembolso, formulário
**Protótipo #11.** Rotas `reembolsos.create`/`store`, `edit`/`update`.
Campos: colaborador (só ativos), conta de destino, descrição, categoria
da despesa (Viagem/Alimentação/Material/Transporte/Outro), valor, data
da solicitação (não futura), comprovante (upload, pdf/jpg/jpeg/png/xml,
máx. 10MB), observações.

### Tarefa 12, Reembolso, modal de rejeição
**Protótipo #12.** Rota `reembolsos.status`. Motivo obrigatório, vai para
o histórico. **Regra:** não existe atalho de Pendente direto para Pago,
precisa passar por Aprovado antes.

### Tarefa 13, Colaboradores, listagem
**Protótipo #13.** Rota `colaboradores.index`. Filtros: busca (nome, CPF,
cargo ou e-mail, debounce), status, departamento (alimentado pelos
departamentos existentes no banco). Colunas: Nome (+ CPF formatado
abaixo), Cargo (+ Departamento), Contrato (CLT/PJ/Estágio/Autônomo),
Admissão, Salário base, Contas (**fica vermelho quando é zero**, sem conta
não há como lançar folha), Status. Paginação 15/30/50. Estenda
`StatusBadge` para o vocabulário de colaborador.

### Tarefa 14, Colaborador, detalhe
**Protótipo #14.** Rota `colaboradores.show`. Layout de três colunas
(conteúdo principal ocupa duas, painel de contas ocupa uma, ver Tarefa
17). Bloco "Dados cadastrais" com os oito pares (CPF, Cargo, Departamento,
Tipo de contrato, Admissão, Salário base, E-mail, Telefone). Quando
`data_desligamento` existe, aviso em faixa cinza com o texto exato do
briefing. Bloco "Últimos pagamentos" (até 20 linhas).

### Tarefa 15, Colaborador, formulário
**Protótipo #15.** Rotas `create`/`store`, `edit`/`update`. Dois cartões:
Dados pessoais (nome, CPF com máscara e dígito verificador, e-mail,
telefone) e Vínculo (cargo, departamento, tipo, status, admissão,
salário, observações). Ações no rodapé alinhadas à direita: "Cancelar"
discreto e "Cadastrar" / "Salvar alterações" primário.

### Tarefa 16, Colaborador, modal de desligamento
**Protótipo #16.** Rota `colaboradores.desligar`. Campos: data de
desligamento (não anterior à admissão) e observações. Após confirmar,
usar a mensagem exata do briefing sobre histórico preservado e rescisão
como lançamento avulso.

### Tarefa 17, Painel de contas bancárias
**Protótipo #17.** Componente `ContasBancarias`, reaproveitado entre
colaborador e fornecedor (Tarefas 14 e 20). Cada conta mostra: banco em
destaque, "Ag. 1234, C/C 56789-0" (tipo abreviado "C/C" ou "Poup."),
titular e documento formatado, chave Pix com ícone de raio em azul quando
houver, selo "Principal" em laranja claro ou badge "Inativa", ações
contextuais (Tornar principal, Inativar, Reativar) conforme as regras da
Seção 10 (uma única conta principal por beneficiário, não dá para
desmarcar sem substituir, contas são inativadas nunca excluídas, não é
possível inativar a principal se houver outra ativa). Estado vazio com o
texto exato do briefing sobre destino padrão.

### Tarefa 18, Modal de conta bancária/Pix
**Protótipo #18.** Rotas `contas.store`/`update`/`principal`/`inativar`/
`reativar`. 11 campos (Seção 7): banco, código, agência, conta, dígito,
tipo de conta, nome do titular, CPF/CNPJ do titular, tipo da chave Pix,
chave Pix, principal (checkbox). Validação de chave por tipo (CPF 11
dígitos, CNPJ 14, e-mail, telefone com `+55` e 10 ou 11 dígitos, aleatória
UUID v4), com mensagem deixando claro que é só formato, sem consulta ao
DICT.

### Tarefa 19, Fornecedores, listagem
**Protótipo #19.** Rota `fornecedores.index`, já paginado com
`contratos_count` e `contas_bancarias_count`. Filtros de busca, status e
tipo já existem no backend. Estenda `StatusBadge` para o vocabulário de
fornecedor.

### Tarefa 20, Fornecedor, detalhe
**Protótipo #20.** Rota `fornecedores.show`, já entrega contas bancárias,
contratos com categoria, últimos 20 pagamentos. Espelha a estrutura da
Tarefa 14 (colaborador), reaproveitando `ContasBancarias`.

### Tarefa 21, Fornecedor, formulário
**Protótipo #21.** Campos (Seção 7): tipo de pessoa (PF/PJ), razão
social/nome, nome fantasia, documento (CPF/CNPJ, único, validado conforme
o tipo escolhido), tipo de fornecedor, e-mail, telefone, endereço,
status, observações. **Comportamento dinâmico principal:** a máscara do
documento muda com o tipo de pessoa escolhido.

---

## Prioridade 3, contratos, categorias e relatórios

### Tarefa 22, Contratos, listagem
**Protótipo #22.** Rota `contratos.index`, já paginado com fornecedor e
categoria. Filtros de status, tipo e fornecedor já existem no backend.
Estenda `StatusBadge` para o vocabulário de contrato.

### Tarefa 23, Contrato, detalhe
**Protótipo #23.** Rota `contratos.show`, já entrega fornecedor,
categoria, conta, anexos e os últimos 20 pagamentos que o contrato gerou,
mais o próximo vencimento.

### Tarefa 24, Contrato, formulário
**Protótipo #24.** Campos (Seção 7): fornecedor (só ativos), categoria,
conta de destino (se vazia, usa a principal do fornecedor), descrição,
tipo (Pontual/Recorrente), valor, periodicidade (obrigatória se
recorrente), dia de vencimento 1 a 31 (obrigatório se recorrente), data
de início, data de término (não anterior ao início), status,
observações. Escolher "Recorrente" revela periodicidade e dia de
vencimento, que alimentam a rotina de geração automática das 6h.

### Tarefa 25, Categorias, listagem
**Protótipo #25.** Rota `categorias.index`, lista completa **sem
paginação**, com `pagamentos_count`. Filtro por tipo. Tabela curta, não
precisa do aparato de listagem pesada das outras telas.

### Tarefa 26, Categoria, formulário
**Protótipo #26.** Cabe em `Dialog` (modal), não precisa de página
própria. Campos: nome (único), tipo (select com os 8 tipos), descrição,
ativo (checkbox).

### Tarefa 27, Relatórios
**Protótipo #27.** Rota `relatorios.index`, já entrega `resumo{total,
quantidade}`, `porStatus[]{status,rotulo,total,quantidade}`,
`porCategoria[]{nome,total}`. Filtros de período, categoria e status já
existem no backend.

### Tarefa 28, Relatórios, modal de exportação
**Protótipo #28.** Rota `relatorios.exportar`. `Dialog` simples: escolher
formato (Excel ou PDF), mantendo os filtros aplicados na tela.
- **Confirme antes de começar:** o `ESTADO.md` marca a exportação em si
  como pendente no backend (só os agregados da Tarefa 27 estão prontos).
  Antes de implementar, verifique no repositório se `relatorios.exportar`
  já responde de verdade. Se não responder, construa a interface do modal
  mesmo assim (ela não depende do endpoint pra existir), mas deixe a ação
  de exportar desabilitada com um aviso ("Exportação em breve") em vez de
  ligar num endpoint que ainda não existe, e me avise que essa tarefa
  ficou com uma dependência de backend pendente antes de pedir aprovação
  pra commit.

### Tarefa 29, Auditoria
**Protótipo #29.** Rota `auditoria.index`, já paginado, `{log, descricao,
registro_tipo, registro_id, usuario, alteracoes, created_at}`. Filtros de
módulo, usuário e período já existem no backend. Lista longa, cada linha
mostra as diferenças de campo (de/para) de forma legível, não como JSON
cru.

### Tarefa 30, Componente de anexos
**Protótipo #30** (referência visual dentro da tela de Pagamento,
detalhe). Rotas `anexos.store`, `anexos.download` (rota autenticada,
**nunca** link direto para o arquivo, os dados são bancários), `anexos.
destroy`. Upload, lista, download, remover. Componente único, reaproveitado
em Pagamento (Tarefa 4) e Reembolso (Tarefa 10), construir aqui e depois
integrar nas duas telas.

---

## Prioridade 4, acesso e sistema

### Tarefa 31, Login
**Protótipo #31.** Ainda no visual padrão do Breeze, restilizar com a
marca. Campos: e-mail, senha, lembrar-me, link "esqueceu a senha?". **Sem
link de cadastro** (rotas de registro foram removidas de propósito, não
adicionar de volta).

### Tarefa 32, Esqueci a senha
**Protótipo #32.** Padrão Breeze, restilizar visual.

### Tarefa 33, Redefinir senha
**Protótipo #33.** Padrão Breeze, restilizar visual.

### Tarefa 34, Confirmar senha
**Protótipo #34.** Padrão Breeze, restilizar visual.

### Tarefa 35, Verificar e-mail
**Protótipo #35.** Padrão Breeze, restilizar visual.

### Tarefa 36, Meu perfil
**Protótipo #36.** Padrão Breeze: dados da conta, alterar senha, excluir
conta. Restilizar mantendo os três blocos.

### Tarefa 37, Usuários, listagem e formulário
**Protótipo #37.** **Atenção: esta não é só uma tarefa de frontend.** A
permissão `usuarios.gerenciar` existe no catálogo (`App\Support\
Permissoes`) e é concedida ao Administrador, mas não há controller, rota
nem tela hoje. O `ESTADO.md` confirma que isso vai além do que o briefing
original apontava: a tabela de "Próximas fases" (2 a 8) não reserva
nenhuma fase pra essa tela, ou seja, ela está fora do roadmap atual,
alguém precisa decidir quando ela entra. Antes de montar a interface,
confirme comigo se você quer que o Claude Code também crie o backend
(controller, rotas, FormRequest, autorização, e provavelmente uma entrada
nova na tabela de fases do `ESTADO.md`) nesta mesma tarefa, ou se prefere
separar em uma tarefa de backend à parte antes de chegar aqui. Interface
esperada: listagem de usuários com ação de ativar/desativar, formulário
com nome, e-mail, perfil e vínculo opcional a um colaborador.

### Tarefa 38, Erro 403, sem permissão
**Protótipo #38.** Tela genérica, explica que o perfil atual não tem
acesso ao módulo, com um caminho de volta ao Dashboard.

### Tarefa 39, Erro 404
**Protótipo #39.** Tela genérica de página não encontrada.

### Tarefa 40, Estados vazios do primeiro uso
**Protótipo #40.** Definir uma vez e reaplicar em toda listagem:
**carregando** (skeleton), **vazio por filtro** (com ação de limpar
filtros), **vazio de verdade** (primeiro uso, com chamada para cadastrar)
e **erro de carregamento** (com ação de tentar novamente). Depois de
pronta, essa tarefa vira um retrofit rápido nas listagens já feitas
(Tarefas 3, 9, 13, 19, 22, 25, 29), sinalize isso como um passo final
depois da aprovação desta tarefa.

---

## Ordem de execução

Siga a ordem numérica de 1 a 40. Não pule Tarefa 1 e 2, mesmo que pareçam
"estruturais demais", é onde os tokens e os componentes compartilhados
(`StatusBadge`, `CardIndicador`) nascem, e todas as telas seguintes
dependem deles.
