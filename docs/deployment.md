# Deploy em produção

## Requisitos

- PHP 8.2+ com as extensões padrão do Laravel (`pdo_mysql`, `mbstring`, `openssl`, `tokenizer`,
  `xml`, `ctype`, `json`, `bcmath`, `fileinfo`).
- MySQL/MariaDB 8+/10.6+.
- Node 18+ (apenas para o build do frontend — não é necessário no servidor de produção após
  gerar os assets).
- Um processo de cron capaz de disparar `php artisan schedule:run` a cada minuto.

## Variáveis de ambiente essenciais

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://sua-instancia.com

DB_CONNECTION=mysql
DB_HOST=...
DB_DATABASE=...
DB_USERNAME=...
DB_PASSWORD=...

CACHE_STORE=database
SESSION_DRIVER=database
SESSION_SECURE_COOKIE=true    # obrigatório atrás de HTTPS
QUEUE_CONNECTION=sync

MAIL_MAILER=smtp
MAIL_HOST=...
```

`APP_DEBUG=false` é obrigatório em produção — com `true`, exceptions expõem stack traces e
variáveis de ambiente na resposta HTTP. `SESSION_SECURE_COOKIE=true` só deve ser usado atrás de
HTTPS (o padrão do framework, sem essa variável, detecta automaticamente).

## Passo a passo

```bash
composer install --no-dev --optimize-autoloader
npm ci
npm run build

php artisan key:generate --force     # apenas na primeira instalação
php artisan migrate --force
php artisan db:seed --force          # apenas na primeira instalação (permissões + demo)

php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
```

`event:cache` é importante aqui: o Laravel 12 descobre os listeners em `app/Listeners`
escaneando o código-fonte a cada boot em desenvolvimento; em produção, `event:cache` gera um
manifesto estático para evitar esse custo a cada requisição.

## Scheduler

Os comandos agendados (`routes/console.php`) — verificação de SLA a cada 5 minutos, digest de
estoque crítico e lembrete de avaliação pendente diários — dependem do cron do Laravel estar
ativo:

```cron
* * * * * cd /caminho/da/aplicacao && php artisan schedule:run >> /dev/null 2>&1
```

## Migrações em produção

`php artisan migrate --force` é necessário porque, por padrão, o Artisan pede confirmação
interativa em ambiente `production`. Sempre rode `php artisan migrate:status` antes, num
ambiente de staging equivalente, para confirmar que nenhuma migration destrutiva será aplicada
sem revisão.

## Checklist pós-deploy

- [ ] `APP_DEBUG=false` e `APP_ENV=production`
- [ ] HTTPS ativo e `SESSION_SECURE_COOKIE=true`
- [ ] Cron do scheduler configurado e confirmado rodando (`php artisan schedule:list`)
- [ ] `/up` (health check padrão do Laravel) responde `200`
- [ ] Backup automático do banco configurado
- [ ] `storage/` e `bootstrap/cache/` com permissão de escrita para o usuário do servidor web
