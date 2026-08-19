# Database

O Operix usa **MySQL/MariaDB** (via XAMPP em desenvolvimento) como banco principal, com Eloquent ORM. Todas as tabelas de domínio pertencem a uma `company` (multi-tenancy single-database) através da coluna `company_id`, exceto tabelas filhas/pivot que herdam o tenant através da entidade pai (ex.: `customer_addresses` herda de `customers`).

> Nota de arquitetura: o spec original do produto especifica PostgreSQL. O ambiente de desenvolvimento disponível só tinha MariaDB (XAMPP) pronto para uso — decisão registrada e aprovada com o usuário durante o planejamento da Fase 1. O Eloquent abstrai a maior parte das diferenças; migrar para Postgres no futuro exigiria apenas ajustes pontuais (nenhuma feature específica de um dialeto foi usada).

## Convenções

- **Chaves primárias**: `id` auto-incremento (`bigIncrements`) em todas as tabelas, exceto pivots puros (`team_members`) que usam chave composta, e `notifications` (UUID, padrão do Laravel).
- **Soft deletes**: aplicado em entidades de negócio que podem ser "arquivadas" sem perder histórico (`companies`, `users`, `customers`, `equipment`, `technicians`, `teams`, `suppliers`, `products`, `work_orders`, `appointments`). Tabelas de log/histórico/pivot não têm soft delete.
- **Enums de domínio**: status, prioridade e tipos são armazenados como `string` (não `ENUM` nativo da coluna, para manter portabilidade entre MySQL/Postgres) e mapeados para PHP backed enums em `app/Enums/` via cast do Eloquent (`WorkOrderStatus`, `WorkOrderPriority`, `TechnicianStatus`, `AppointmentStatus`, `SlaStatus`, `InventoryMovementType`, `FinancialTransactionType`, `UserRole`).
- **JSON**: usado apenas onde a estrutura é inerentemente variável e não se beneficiaria de normalização — `companies.settings` (configuração livre por empresa), `audit_logs.old_values`/`new_values` (diff genérico de qualquer model) e a tabela padrão `notifications.data` do Laravel.

## Diagrama de dependências (ordem de criação das migrations)

```
companies
  └── users (company_id nullable → super admins não pertencem a nenhuma empresa)
      └── roles/permissions (spatie/laravel-permission, escopado por company_id via "teams")

customers ← company
  ├── customer_addresses
  └── customer_contacts

equipment ← company, customer

technicians ← company, user (opcional), supervisor (self)
  └── technician_skills

teams ← company, supervisor (technician)
  └── team_members (pivot: team × technician)

sla_policies ← company
holidays ← company

product_categories ← company (self, árvore de categorias)
suppliers ← company
products ← company, product_category, supplier

work_orders ← company, customer, customer_address, equipment, technician, team, sla_policy, created_by (user)
  ├── work_order_status_history
  ├── work_order_items
  ├── work_order_attachments        (inclui campos de assinatura: signer_name/document/ip)
  ├── work_order_checklists
  │     └── work_order_checklist_items
  ├── work_order_materials ← product
  └── work_order_services ← technician

appointments ← company, work_order, technician, team
dispatches   ← company, work_order, technician, dispatched_by (user)
sla_events   ← work_order

inventory_movements ← company, product

financial_transactions ← company, work_order (opcional), customer (opcional)

notifications  (tabela padrão do Laravel — polimórfica)
audit_logs     ← company (opcional), user (opcional) — auditable polimórfico
ratings        ← company, work_order (unique), customer, technician
```

## Tabelas por módulo

| Módulo | Tabelas |
|---|---|
| Plataforma | `companies`, `users`, `roles`, `permissions`, `model_has_roles`, `model_has_permissions`, `role_has_permissions` |
| Clientes | `customers`, `customer_addresses`, `customer_contacts`, `equipment` |
| Equipe | `technicians`, `technician_skills`, `teams`, `team_members` |
| Ordens de Serviço | `work_orders`, `work_order_status_history`, `work_order_items`, `work_order_attachments`, `work_order_checklists`, `work_order_checklist_items`, `work_order_materials`, `work_order_services` |
| SLA | `sla_policies`, `sla_events`, `holidays` |
| Agenda / Despacho | `appointments`, `dispatches` |
| Estoque | `product_categories`, `suppliers`, `products`, `inventory_movements` |
| Financeiro | `financial_transactions` |
| Notificações / Auditoria / Avaliações | `notifications`, `audit_logs`, `ratings` |

## Multi-tenancy (RBAC)

O `spatie/laravel-permission` está configurado com a feature de **teams** habilitada (`config/permission.php`), usando `company_id` como `team_foreign_key`. Isso significa que roles e permissions atribuídas a um usuário são automaticamente escopadas pela empresa corrente — a mesma pessoa pode (no futuro) ter papéis diferentes em empresas diferentes, e um usuário de uma empresa nunca enxerga roles de outra.

### Enforcement (Fase 4)

- **`App\Support\CurrentCompany`** — holder estático do `company_id` ativo na requisição corrente. `null` significa "sem restrição de tenant" (guests, comandos artisan, ou um `SUPER_ADMIN` com `company_id` nulo).
- **`App\Http\Middleware\EnsureCompanyContext`** — registrado no grupo `web` (`bootstrap/app.php`), resolve `CurrentCompany` a partir de `$request->user()->company_id` a cada requisição.
- **`App\Models\Concerns\BelongsToCompany`** — trait aplicado aos 17 models de domínio que pertencem a uma empresa (`User`, `Customer`, `Technician`, `Team`, `WorkOrder`, `Equipment`, `SlaPolicy`, `Holiday`, `ProductCategory`, `Supplier`, `Product`, `Appointment`, `Dispatch`, `InventoryMovement`, `FinancialTransaction`, `AuditLog`, `Rating`). Adiciona um global scope que filtra por `company_id` quando `CurrentCompany` está definido, e um observer `creating` que auto-preenche `company_id`. Tabelas filhas/pivot (`customer_addresses`, `work_order_items`, `team_members` etc.) não usam o trait — herdam o isolamento através da FK para o pai.
- **`App\Support\PermissionsTeamResolver`** — implementação customizada de `PermissionsTeamResolver` do spatie/permission que usa `CurrentCompany::id()` como team id por padrão (sem precisar chamar `setPermissionsTeamId()` manualmente em todo lugar), mas ainda permite override explícito quando necessário (ex.: um super admin gerenciando outra empresa).

Cobertura de testes em `tests/Feature/Tenancy/` — isolamento de queries entre duas empresas, auto-preenchimento de `company_id`, no-op do scope sem contexto de tenant, resolução do contexto a partir do login HTTP, e o resolver de permissões do spatie.

## RBAC (Fase 5)

- **`App\Support\Permissions`** — lista canônica das 30 permissions granulares (seção 10 do spec: `customers.*`, `technicians.*`, `teams.*`, `equipment.*`, `work_orders.*`, `scheduling.*`, `dispatch.*`, `inventory.*`, `financial.*`, `reports.view`, `automations.manage`, `audit.view`, `settings.manage`, `users.manage`) e o mapa padrão role → permissions para os 6 papéis **escopados por empresa** (`admin`, `manager`, `dispatcher`, `technician`, `financial`, `support`).
- **`SUPER_ADMIN` não é uma role do spatie/permission** — como é um papel de controle da plataforma inteira (seção 10: "Controle completo da plataforma"), não faz sentido escopá-lo por tenant como as demais roles. Em vez disso é a flag `users.is_super_admin` (boolean), verificada via `Gate::before` em `AppServiceProvider` — quando `true`, todas as autorizações são concedidas automaticamente, sem precisar de permissions/roles atribuídas.
- **`database/seeders/PermissionSeeder.php`** — cria as 30 permissions globais (rodado uma vez, incluído no `DatabaseSeeder`).
- **`App\Actions\SeedDefaultCompanyRoles`** — cria as 6 roles + sincroniza permissions para uma empresa específica, usado no onboarding (Fase 66) e nos seeders de demo (Fase 26). Fixa o team id do spatie/permission durante a operação e libera de volta ao modo "segue `CurrentCompany` dinamicamente" ao final (`setPermissionsTeamId(null)` limpa o pin explícito, não fixa `null` como team — isso é o que evita o bug de "travar" o resolver fora do contexto correto).
- **Policies base**: `CustomerPolicy`, `TechnicianPolicy`, `WorkOrderPolicy` em `app/Policies/` — cada método de autorização checa a permission via `$user->can('recurso.acao')` **e** reforça o isolamento de tenant explicitamente (`$user->company_id === $model->company_id`), mesmo que o global scope do `BelongsToCompany` já devesse impedir o model de outra empresa de ser resolvido — defesa em profundidade (spec §50: autorização nunca deve depender só de uma query scope).

Cobertura de testes em `tests/Feature/Rbac/` — seeding de roles por empresa (isolado entre tenants, sem role `super_admin`), negação de acesso sem a permission certa, concessão via role, negação de policy mesmo com a permission certa quando o recurso é de outra empresa, e bypass total do super admin (permissions e tenant isolation). Validado também manualmente contra MySQL real.

## Modelos Eloquent

Todas as tabelas de domínio têm um model Eloquent correspondente em `app/Models/`, com relacionamentos (`BelongsTo`/`HasMany`/`BelongsToMany`), casts de enum e casts decimais para colunas monetárias/quantidade já configurados. Ver `app/Models/WorkOrder.php` como referência do model mais completo (11 relacionamentos).

## Verificação

```bash
php artisan migrate:fresh   # roda as 30 migrations do zero sem erro
php artisan tinker          # smoke test manual de criação + relacionamentos
```
