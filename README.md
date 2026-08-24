# Operix

**Plataforma Enterprise de Field Service Management** — gestão completa de ordens de serviço, técnicos de campo, SLA, agenda, estoque, financeiro e relatórios, multiempresa (multi-tenant), construída em Laravel 12 + Livewire 3.

---

## Sumário

- [Visão geral](#visão-geral)
- [Stack](#stack)
- [Funcionalidades](#funcionalidades)
- [Arquitetura](#arquitetura)
- [Ambiente de desenvolvimento](#ambiente-de-desenvolvimento)
- [Testes e qualidade](#testes-e-qualidade)
- [Estrutura de pastas](#estrutura-de-pastas)
- [Documentação técnica](#documentação-técnica)
- [Roadmap](#roadmap)

## Visão geral

O Operix cobre o ciclo de vida completo de uma operação de field service: da abertura de uma
ordem de serviço até o fechamento financeiro, passando por agendamento, despacho de técnicos,
execução em campo (checklist, evidências, assinatura do cliente), consumo de estoque e
indicadores operacionais — tudo isolado por empresa (multi-tenancy single-database) e com
controle de acesso granular por papel.

## Stack

| Camada | Tecnologia |
|---|---|
| Backend | PHP 8.2, Laravel 12 |
| Frontend | Livewire 3.8, Alpine.js, Tailwind CSS 4, Vite |
| Banco de dados | MySQL/MariaDB |
| Autenticação de API | Laravel Sanctum (tokens pessoais) |
| Autorização | spatie/laravel-permission (RBAC multiempresa via *teams*) |
| Testes | Pest |
| Qualidade de código | Laravel Pint (estilo), Larastan/PHPStan nível 5 (análise estática) |

## Funcionalidades

- **Ordens de serviço** — ciclo de vida completo (novo → triagem → agendado → atribuído →
  em deslocamento → em atendimento → resolvido → concluído), com timeline de auditoria,
  itens faturáveis, checklist de execução, anexos (fotos/vídeos/assinatura) e materiais
  consumidos do estoque.
- **SLA** — cálculo de prazo considerando horário comercial e feriados (fixos e recorrentes),
  com indicador visual de progresso e violação detectada tanto em transições de status quanto
  proativamente por um comando agendado.
- **Agenda e Despacho** — calendário diário/semanal/mensal com prevenção de conflito de
  horário por técnico/equipe, e uma central de despacho com atribuição via drag-and-drop
  nativo (sem bibliotecas externas).
- **Portal do técnico** — fluxo mobile-first para o técnico em campo: deslocamento, chegada,
  atendimento, checklist, diagnóstico, evidências, assinatura do cliente via `<canvas>` nativo
  e avaliação coletada na hora.
- **Estoque** — produtos, categorias, fornecedores, movimentações com trilha de auditoria
  completa, e consumo de materiais em OS com dedução/estorno automático de saldo.
- **Financeiro** — receita e custo calculados por OS (itens faturados + materiais consumidos +
  lançamentos manuais), margem e razão financeira da empresa.
- **Relatórios** — indicadores operacionais, SLA, produtividade por técnico, financeiro e
  estoque crítico, com exportação em CSV.
- **Notificações** — eventos de domínio (OS atribuída/concluída/cancelada, SLA violado)
  entregues por banco de dados, e-mail e webhook configurável por empresa.
- **Automação** — verificação proativa de SLA, digest diário de estoque crítico e lembrete de
  avaliação pendente, todos via comandos agendados (`routes/console.php`).
- **Auditoria** — trilha de ações críticas (login, gestão de usuários/papéis, lançamentos
  financeiros, exclusões, configuração de webhook).
- **API REST** (`/api/v1`) — autenticada por token pessoal (Sanctum), com filtros, ordenação
  e paginação, reaproveitando as mesmas policies e o mesmo isolamento de tenant da aplicação
  web.
- **Busca global** (Ctrl+K) e **dashboard executivo** com KPIs do mês e checklist de
  onboarding.

## Arquitetura

Multi-tenancy *single-database*: toda tabela de domínio carrega uma coluna `company_id`,
isolada automaticamente por um global scope (`App\Models\Concerns\BelongsToCompany`) resolvido
a partir do usuário autenticado (`App\Support\CurrentCompany`). RBAC é feito com
`spatie/laravel-permission` usando a feature de *teams* — papéis e permissões são escopados por
empresa, exceto o `SUPER_ADMIN`, que é uma flag de plataforma (`users.is_super_admin`) e não uma
role tenant-scoped.

Detalhes completos em [`docs/architecture.md`](docs/architecture.md).

## Ambiente de desenvolvimento

Pré-requisitos: PHP 8.2+, Composer, Node 18+, MySQL/MariaDB.

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate

# configure DB_* no .env, então:
php artisan migrate --seed
npm run build   # ou `npm run dev` para hot-reload durante o desenvolvimento

php artisan serve
```

A aplicação sobe em `http://localhost:8000`. O comando `sla:check` (verificação proativa de
SLA) e os comandos de automação diária precisam do scheduler do Laravel rodando
(`php artisan schedule:work` em desenvolvimento).

## Testes e qualidade

```bash
vendor/bin/pest              # suíte completa de testes de feature
vendor/bin/pint               # formatação de código (Laravel Pint)
vendor/bin/phpstan analyse    # análise estática (Larastan, nível 5)
```

A suíte cobre autenticação, isolamento de tenant, RBAC, todo o ciclo de vida de uma OS, cálculo
de SLA (incluindo feriados e fins de semana), conflito de agendamento, estoque, financeiro,
notificações, auditoria, API e uma rede de regressão dedicada (varredura de rotas protegidas,
matriz de permissões, contagem de queries para detectar N+1).

## Estrutura de pastas

```
app/
├── Actions/          Operações pontuais e reutilizáveis (ex.: geração de número de OS)
├── Console/Commands/ Comandos agendados (SLA, digest de estoque, lembretes)
├── Enums/            Enums de domínio (status, prioridade, tipos)
├── Events/           Eventos de domínio (WorkOrderAssigned, SlaBreached...)
├── Exceptions/       Exceções de domínio
├── Http/
│   ├── Controllers/Api/V1/  Controllers da API REST
│   ├── Middleware/
│   ├── Requests/Api/V1/     Form Requests da API
│   └── Resources/           API Resources (contrato de resposta)
├── Listeners/         Listeners de eventos (auto-descobertos pelo Laravel 12)
├── Livewire/          Componentes Livewire, organizados por módulo
├── Models/            Models Eloquent
├── Notifications/     Notificações (database/e-mail/webhook)
├── Policies/           Autorização por recurso
├── Services/           Regras de negócio centralizadas (SlaService, StockService...)
└── Support/            Utilitários transversais (CurrentCompany, Permissions...)
database/
├── factories/  seeders/  migrations/
resources/
├── views/livewire/    Views dos componentes Livewire, espelhando app/Livewire
├── views/components/  Componentes Blade reutilizáveis (design system)
routes/
├── web.php  api.php  console.php
tests/Feature/  Testes de feature, organizados por módulo
docs/            Documentação técnica detalhada
```

## Documentação técnica

- [`docs/architecture.md`](docs/architecture.md) — multi-tenancy, RBAC, camada de serviços, eventos/notificações
- [`docs/database.md`](docs/database.md) — schema, convenções e diagrama de dependências
- [`docs/api.md`](docs/api.md) — API REST v1: autenticação, endpoints, contrato de resposta
- [`docs/security.md`](docs/security.md) — medidas de segurança implementadas
- [`docs/deployment.md`](docs/deployment.md) — guia de deploy em produção
- [`docs/roadmap.md`](docs/roadmap.md) — status das 26 fases e backlog pós-lançamento

## Roadmap

O projeto seguiu um roadmap de 26 fases incrementais (fundação → banco de dados → autenticação →
multi-tenancy → RBAC → módulos de negócio → API → qualidade → produção), todas concluídas. Ver
[`docs/roadmap.md`](docs/roadmap.md) para o status de cada fase, como popular uma empresa de
demonstração completa, e o backlog de itens deliberadamente fora do escopo original. Progresso e
decisões de cada fase também estão registrados no histórico de commits do repositório.
