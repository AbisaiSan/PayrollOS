# PayrollOS

Módulo da Corebanx para gestão interna de pagamentos. Centraliza folha de pagamento,
fornecedores, prestadores de serviço e reembolsos de colaboradores, com contas
bancárias, chaves Pix e acompanhamento de vencimentos.

**O sistema não executa pagamentos.** Ele registra, organiza e acompanha o processo;
a confirmação de que o dinheiro saiu é sempre manual.

## Stack

| Camada | Tecnologia |
|---|---|
| Backend | Laravel 13 (PHP 8.3) |
| Frontend | Vue 3 + TypeScript + Inertia.js |
| UI | PrimeVue 4 + Tailwind CSS 3 |
| Banco | PostgreSQL |
| Testes | Pest 4 |

Pacotes principais: `spatie/laravel-permission` (perfis), `spatie/laravel-activitylog`
(auditoria), `spatie/laravel-medialibrary`, `maatwebsite/excel` e `barryvdh/laravel-dompdf`
(exportação de relatórios).

> O plano original previa Laravel 11/12; o projeto foi criado na versão estável
> atual (13), que é compatível com tudo o que o plano descreve.

## Requisitos

- PHP >= 8.3 com as extensões `pdo_pgsql`, `mbstring`, `openssl`, `fileinfo`, `curl`,
  `zip`, `gd`, `intl`, `exif`, `bcmath`
- Composer 2
- Node >= 22 (ver `.nvmrc`)
- PostgreSQL 14+

## Instalação

```bash
composer install
npm install

cp .env.example .env
php artisan key:generate

# ajuste DB_DATABASE, DB_USERNAME e DB_PASSWORD no .env
php artisan migrate --seed

npm run dev
php artisan serve
```

O seeder cria os perfis, as permissões, as categorias iniciais e um usuário
administrador (`admin@corebanx.com.br` / `password` por padrão — troque via
`PAYROLLOS_ADMIN_EMAIL` e `PAYROLLOS_ADMIN_PASSWORD` antes de rodar em produção).

## Rotinas agendadas

Duas rotinas rodam diariamente e dependem do agendador do Laravel
(`php artisan schedule:run` no cron a cada minuto):

```bash
# Cria os lançamentos dos contratos recorrentes alguns dias antes do vencimento
php artisan payrollos:gerar-lancamentos [--dias=5] [--dry-run]

# Move para Atrasado o que venceu sem confirmação
php artisan payrollos:marcar-atrasados
```

`--dry-run` executa a rotina real dentro de uma transação e desfaz ao final, para
conferir o que seria gerado.

## Organização do código

```
app/
  Enums/          Estados e classificações do domínio, com rótulos em pt-BR
  Models/         Eloquent + traits compartilhados (contas, anexos, histórico)
  Services/       Regras de negócio (lançamento, status, contratos recorrentes)
  Policies/       Autorização por módulo
  Rules/          Validação de CPF, CNPJ e chave Pix
  Support/        Perfis, permissões e utilitários de documento
resources/js/
  Pages/          Uma pasta por módulo
  Components/     Componentes reaproveitados entre módulos
  Composables/    Formatação, permissões e utilitários de UI
```

Regras de negócio ficam em Services, não em Controllers. Toda mudança de status de
pagamento ou reembolso passa por um Service, que valida a transição e grava a
trilha em `historico_status`.

## Perfis de acesso

| Perfil | O que faz |
|---|---|
| Administrador | Acesso total |
| Financeiro | Lança, edita e confirma pagamentos; aprova reembolsos |
| Gestor | Visualiza e solicita reembolsos |
| Leitura | Apenas relatórios e consultas |

## Qualidade

```bash
php artisan test        # Pest
vendor/bin/pint         # Formatação PHP
npm run type-check      # TypeScript
npm run build           # Build de produção
```

## Estado atual

Ver [`docs/ESTADO.md`](docs/ESTADO.md) para o que já está pronto e o que cada fase
seguinte entrega. O plano de referência está em
[`docs/plano-original.md`](docs/plano-original.md).
