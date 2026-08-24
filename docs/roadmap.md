# Roadmap

## Status: as 26 fases do plano original estão concluídas

| # | Fase | Escopo |
|---|---|---|
| 1 | Foundation | Scaffold Laravel 12, Livewire, Tailwind, tooling de qualidade |
| 2 | Database | Schema completo (30+ tabelas) |
| 3 | Authentication | Login, rate limiting, reset de senha |
| 4 | Multi-tenancy | Isolamento por `company_id`, global scope, middleware |
| 5 | RBAC | spatie/laravel-permission com teams, 6 papéis + SUPER_ADMIN |
| 6 | Customers | CRUD, endereços, contatos |
| 7 | Technicians | Cadastro, especialidades, produtividade |
| 8 | Teams | Equipes, supervisor, capacidade |
| 9 | Work Orders | Módulo central: estados, prioridades, timeline |
| 10 | SLA Engine | Horário comercial, feriados, indicador visual |
| 11 | Scheduling | Agenda diária/semanal/mensal, prevenção de conflito |
| 12 | Dispatch Center | Kanban com drag-and-drop nativo |
| 13 | Technician Portal | Fluxo mobile: checklist, diagnóstico, evidências, assinatura, avaliação |
| 14 | Inventory | Produtos, movimentações, consumo em OS |
| 15 | Financial | Receita/custo/margem por OS |
| 16 | Reports | Operacional, SLA, técnicos, financeiro, estoque |
| 17 | Notifications | Eventos de domínio → database/e-mail/webhook |
| 18 | Automation | SLA proativo, digest de estoque, lembrete de avaliação |
| 19 | Audit | Trilha de ações críticas + gestão de usuários |
| 20 | API | REST `/api/v1` com Sanctum, filtros, paginação |
| 21 | Testing | Rede de regressão (matriz de permissões, rotas protegidas) |
| 22 | Performance | Cache de relatórios, verificação anti-N+1 |
| 23 | Security Audit | Rate limiting, cabeçalhos de segurança |
| 24 | UI/UX Polish | Dashboard real, busca global, landing page |
| 25 | Documentation | README + `docs/*.md` |
| 26 | Production Readiness | Health check, seed de demonstração |

## Como validar o sistema

```bash
php artisan migrate:fresh --seed              # schema + permissões globais
php artisan db:seed --class=DemoDataSeeder    # empresa demo completa
```

O seeder de demonstração cria a empresa **Operix Demo Ltda** com um usuário para cada um dos 6
papéis (`admin@demo.operix.test` até `support@demo.operix.test`, senha `demo12345`), 4 técnicos,
1 equipe, 12 clientes, 5 produtos (2 propositalmente abaixo do estoque mínimo), 5 políticas de
SLA, 9 ordens de serviço cobrindo todo o ciclo de vida (incluindo uma violação de SLA orgânica),
compromissos de agenda, lançamentos financeiros e avaliações de cliente — o suficiente para
explorar cada módulo do sistema sem partir de telas vazias.

## Backlog pós-lançamento (fora do escopo das 26 fases)

Itens identificados durante o desenvolvimento como valiosos, mas deliberadamente fora do escopo
do roadmap original:

- **Modo claro** — o design system foi construído dark-only desde a Fase 1 (decisão registrada
  na Fase 24); um tema claro completo exigiria revisar praticamente todo componente Blade do
  projeto.
- **Filas assíncronas** — hoje todo evento de domínio e notificação é processado de forma
  síncrona na mesma requisição. Para volumes de e-mail/webhook maiores, mover para
  `QUEUE_CONNECTION=database` (ou Redis) com workers dedicados é o próximo passo natural — as
  notificações já usam `Illuminate\Bus\Queueable`, então a migração é apenas de configuração de
  infraestrutura, sem mudança de código de aplicação.
- **Portal do cliente** — hoje a avaliação do cliente é coletada pelo técnico em campo
  (Portal do Técnico, Fase 13); um portal self-service para o cliente acompanhar sua própria OS
  não estava no escopo original.
- **Múltiplas moedas/idiomas** — a plataforma é pt-BR/BRL only; `companies.locale` e
  `companies.currency` já existem no schema como campos preparatórios, mas nenhuma lógica de
  internacionalização foi implementada.
- **Assinatura de contrato/plano SaaS** — não há cobrança, planos ou limites de uso por empresa;
  `companies.trial_ends_at` existe no schema mas não é aplicado em nenhuma regra.
- **2FA** — os campos (`two_factor_secret`, `two_factor_recovery_codes`,
  `two_factor_confirmed_at`) existem na tabela `users` desde a Fase 2 como preparação, mas o
  fluxo de configuração/verificação nunca foi construído.
