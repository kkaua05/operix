# Arquitetura

## Visão geral

O Operix é uma aplicação Laravel 12 monolítica, servida majoritariamente por Livewire 3
(sem SPA/API intermediária para a interface web), com uma API REST separada para integrações
externas. Não há filas assíncronas em uso — todo evento de domínio é processado de forma
síncrona, na mesma requisição que o disparou.

## Multi-tenancy

Modelo *single-database, shared schema*: toda tabela de domínio tem uma coluna `company_id`.
O isolamento é garantido em duas camadas:

1. **Global scope** (`App\Models\Concerns\BelongsToCompany`) — aplicado a todo model que
   pertence a uma empresa. Filtra automaticamente toda query por `company_id` quando
   `App\Support\CurrentCompany::id()` está definido, e preenche `company_id` automaticamente
   na criação (`creating`) quando ainda não foi setado explicitamente.
2. **Policies** — cada policy reforça `$user->company_id === $model->company_id`
   explicitamente, mesmo que o global scope já devesse impedir o model de outra empresa de ser
   resolvido. Defesa em profundidade: a autorização nunca depende só de uma query scope.

`CurrentCompany` é resolvido a partir do usuário autenticado pelo middleware
`App\Http\Middleware\EnsureCompanyContext`, anexado aos grupos `web` e `api`
(`bootstrap/app.php`). Um usuário com `company_id` nulo (reservado para `SUPER_ADMIN`) opera
sem nenhuma restrição de tenant.

Quando um código legitimamente precisa contornar o scope (ex.: um comando agendado que
processa todas as empresas, ou verificar se um registro de outra empresa existe para uma
mensagem de erro), usa-se `Model::withoutCompanyScope()`.

## RBAC

`spatie/laravel-permission`, com a feature de **teams** habilitada
(`config/permission.php`) usando `company_id` como `team_foreign_key`. Papéis e permissões são
escopados por empresa — a mesma permission (`work_orders.view`, por exemplo) é uma linha global
única, mas a atribuição a um usuário é sempre dentro do contexto de uma empresa específica.

- **`App\Support\Permissions`** — lista canônica de todas as permissions do sistema e o mapa
  papel → permissions para os 6 papéis padrão (`admin`, `manager`, `dispatcher`, `technician`,
  `financial`, `support`).
- **`App\Actions\SeedDefaultCompanyRoles`** — cria os 6 papéis e sincroniza as permissions para
  uma empresa nova.
- **`App\Support\PermissionsTeamResolver`** — resolve o *team id* do spatie/permission a partir
  de `CurrentCompany` por padrão, sem exigir `setPermissionsTeamId()` manual em todo lugar.
- **`SUPER_ADMIN`** não é uma role do spatie/permission — é a flag `users.is_super_admin`,
  verificada via `Gate::before` em `AppServiceProvider`, que concede acesso automático a
  qualquer ability quando `true`.

Testado extensivamente em `tests/Feature/Rbac/`, incluindo uma matriz de regressão
(`PermissionMatrixTest`) que garante a consistência interna do mapa papel → permissions.

## Camada de serviços

Regras de negócio que vão além de um simples CRUD vivem em `App\Services\*`, não nos
componentes Livewire — cada serviço é testável isoladamente e reutilizável por múltiplos
pontos de entrada (UI web, comandos agendados, API):

| Serviço | Responsabilidade |
|---|---|
| `WorkOrderStatusService` | Transições de status, timeline, disparo de eventos |
| `SlaService` | Cálculo de prazo (horário comercial + feriados) e status ao vivo |
| `DispatchService` | Atribuição de técnico a uma OS |
| `AppointmentConflictChecker` | Prevenção de conflito de agenda |
| `StockService` | Toda mutação de saldo de estoque, com lock pessimista |
| `FinancialService` | Cálculo de receita/custo/margem por OS e razão da empresa |
| `ReportService` | Agregações dos relatórios, com cache de curta duração |
| `AuditService` | Escrita centralizada em `audit_logs` |

## Eventos e notificações

Eventos de domínio (`App\Events\*`) são disparados pelos serviços acima após a mutação de
estado ser persistida. Os listeners (`App\Listeners\*`) são descobertos automaticamente pelo
Laravel 12 a partir da tipagem do parâmetro em `handle()` — **não são registrados manualmente**
em nenhum service provider; fazer isso duplicaria a execução.

Notificações (`App\Notifications\*`) usam três canais: `database` (sino de notificações no
cabeçalho), `mail` e um canal customizado `App\Notifications\Channels\WebhookChannel` que faz
POST do payload para a URL configurada em `companies.settings->webhook_url`
(Configurações → Notificações). Uma falha de rede no webhook é logada, nunca lançada — um
webhook não pode derrubar o fluxo que o disparou.

## API

Ver [`docs/api.md`](api.md).

## Segurança

Ver [`docs/security.md`](security.md).
