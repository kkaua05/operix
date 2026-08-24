# Segurança

Resumo das medidas de segurança implementadas, resultado da auditoria realizada na fase
dedicada do roadmap (§50). Nenhum item aqui é aspiracional — todos têm um teste de regressão
correspondente em `tests/Feature/Security/` e nos testes de RBAC/tenancy de cada módulo.

## Isolamento de tenant

Ver [`docs/architecture.md`](architecture.md#multi-tenancy). Reforçado em duas camadas
independentes (global scope + policy), testado em praticamente todo módulo do sistema.

## Autenticação e sessão

- Senhas com hash `bcrypt` (cast `'password' => 'hashed'` do Eloquent).
- Login com rate limiting (5 tentativas por e-mail+IP, com lockout progressivo).
- Cookies de sessão `HttpOnly` e `SameSite=Lax` por padrão (config/session.php).
- Toda ação crítica (login, falha de login, gestão de usuários/papéis, lançamentos
  financeiros, exclusões, mudança de webhook) é registrada em `audit_logs`
  (`App\Services\AuditService`).

## Autorização

RBAC granular via `spatie/laravel-permission`, aplicado tanto nos componentes Livewire
(`$this->authorize(...)`) quanto nos controllers da API. Um usuário sem permissão recebe `403`;
um recurso de outra empresa não é sequer resolvido pelo route-model-binding (404) graças ao
global scope de tenant.

## CSRF

Padrão do Laravel: todo formulário HTML tradicional (ex.: logout) inclui `@csrf`; Livewire
gerencia seu próprio mecanismo de proteção (checksum assinado por componente) nas requisições
AJAX que faz.

## XSS

Toda saída de dados do usuário passa pelo escaping automático do Blade (`{{ }}`). Não há uso de
`{!! !!}` com conteúdo controlado por usuário em nenhuma view do projeto — as únicas ocorrências
são o merge de atributos HTML de componentes Blade (`$attributes->merge(...)`, autorado pelo
desenvolvedor no template, não pelo usuário final).

## Mass assignment

Todo formulário monta explicitamente o array de atributos a partir das propriedades públicas do
componente Livewire — nenhum `Model::create($request->all())`. Campos sensíveis
(`is_super_admin`, `company_id`) nunca são expostos como propriedade pública vinculável em
nenhum formulário voltado ao usuário.

## Upload de arquivos

O único ponto de upload (evidências de OS no Portal do Técnico) valida por **MIME real do
arquivo**, não pela extensão declarada pelo cliente — um arquivo `foto.jpg.php` é rejeitado
porque seu conteúdo não corresponde a nenhum dos tipos permitidos
(`jpg,jpeg,png,heic,mp4,mov,pdf`), independentemente do nome.

## API

- Autenticação por token pessoal (Sanctum), nunca por sessão de navegador.
- Rate limiting de 60 req/min por usuário (`App\Providers\AppServiceProvider`).
- Mesmas Policies e mesmo isolamento de tenant da aplicação web — nenhuma regra de autorização
  duplicada ou divergente entre os dois pontos de entrada.

## Cabeçalhos de resposta

`App\Http\Middleware\SetSecurityHeaders`, aplicado globalmente:

| Cabeçalho | Valor | Proteção contra |
|---|---|---|
| `X-Frame-Options` | `DENY` | Clickjacking (embutir o app em um iframe malicioso) |
| `X-Content-Type-Options` | `nosniff` | MIME-sniffing de um upload como executável |
| `Referrer-Policy` | `strict-origin-when-cross-origin` | Vazamento de querystring completa para links externos |

## SQL Injection

Toda query usa o query builder do Eloquent com bindings parametrizados. Não há concatenação de
entrada do usuário em `whereRaw`/`DB::raw` em nenhum ponto do projeto — os poucos usos de SQL
bruto existentes (`selectRaw` para agregações como `COUNT`/`AVG`/`SUM`) usam apenas literais
estáticos, nunca interpolação de variáveis.
