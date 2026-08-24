# API REST

Base URL: `/api/v1`. Toda rota exige autenticação e está sujeita a rate limiting.

## Autenticação

A API usa tokens de acesso pessoal do [Laravel Sanctum](https://laravel.com/docs/sanctum).
Um usuário gera seu próprio token em **Configurações → Tokens de API** (`/settings/api-tokens`)
— o token só é exibido uma vez, no momento da criação.

Envie o token no cabeçalho `Authorization`:

```
Authorization: Bearer {token}
```

O token carrega as mesmas permissões do usuário que o gerou — toda chamada passa pelas mesmas
Policies e pelo mesmo isolamento de tenant (`company_id`) da aplicação web. Um token nunca
enxerga dados de uma empresa diferente da do seu dono.

## Rate limiting

60 requisições por minuto, por usuário autenticado (não por IP — um escritório inteiro atrás do
mesmo IP não compartilha o mesmo limite). Ao exceder, a API responde `429 Too Many Requests`.

## Contrato de resposta

Toda resposta de sucesso tem o payload em `data`:

```json
{ "data": { "id": 1, "number": "OS-00001", "...": "..." } }
```

Listagens paginadas incluem `links` e `meta`:

```json
{
  "data": [ { "...": "..." } ],
  "links": { "first": "...", "last": "...", "prev": null, "next": "..." },
  "meta": { "current_page": 1, "per_page": 20, "total": 42, "...": "..." }
}
```

Erros seguem o formato padrão do Laravel para JSON:

```json
{ "message": "The customer id field is required.", "errors": { "customer_id": ["..."] } }
```

| Situação | Status |
|---|---|
| Token ausente ou inválido | `401` |
| Sem permissão para o recurso | `403` |
| Registro não encontrado (inclui registros de outra empresa) | `404` |
| Validação falhou | `422` |
| Rate limit excedido | `429` |

## Endpoints

### Ordens de serviço

| Método | Rota | Descrição |
|---|---|---|
| `GET` | `/work-orders` | Lista, com filtros e paginação |
| `GET` | `/work-orders/{id}` | Detalhe |
| `POST` | `/work-orders` | Cria uma nova OS |

**Filtros** (`GET /work-orders`): `?status=`, `?priority=`, `?technician_id=`.
**Ordenação**: `?sort=campo` (crescente) ou `?sort=-campo` (decrescente); campos aceitos:
`created_at`, `scheduled_at`, `sla_due_at`, `priority`, `status`. Padrão: `-created_at`.
**Paginação**: `?per_page=` (máximo 100, padrão 20).

Criação (`POST /work-orders`) exige `work_orders.create`:

```json
{
  "customer_id": 1,
  "technician_id": null,
  "category": "Instalação",
  "description": "...",
  "priority": "high",
  "scheduled_at": "2026-09-01T14:00:00"
}
```

### Clientes

| Método | Rota | Descrição |
|---|---|---|
| `GET` | `/customers` | Lista, com busca (`?search=`) e paginação |
| `GET` | `/customers/{id}` | Detalhe |

### Técnicos

| Método | Rota | Descrição |
|---|---|---|
| `GET` | `/technicians` | Lista, com filtro por status (`?status=`) e paginação |
| `GET` | `/technicians/{id}` | Detalhe |

## Exemplo

```bash
curl -H "Authorization: Bearer $TOKEN" \
     -H "Accept: application/json" \
     "https://sua-instancia/api/v1/work-orders?status=in_progress&sort=-sla_due_at"
```
