# Integração com o IXC Provedor (sem API)

A conta do IXC em uso não tem a API/webservice liberada — só existe login/senha do painel web.
Esta integração usa um scraper headless (Playwright) que captura as respostas JSON internas que
a própria tela do IXC consome, em vez de tentar interpretar o HTML renderizado. Ver
[`scripts/ixc-sync/README.md`](../scripts/ixc-sync/README.md) para os detalhes técnicos do
scraper e, principalmente, **os riscos de automatizar o painel de um terceiro** — leia essa
seção antes de habilitar em produção.

## Como está desligado por padrão

`IXC_SYNC_ENABLED=false` (ou ausente) faz o comando `ixc:sync` — já agendado em
`routes/console.php` a cada 10 minutos — sair sem fazer nada. O botão "Sincronizar agora" na
página **IXC** do Operix funciona mesmo com isso desligado, útil para testar manualmente durante
a calibração.

## Configuração

```env
IXC_SYNC_ENABLED=true
IXC_BASE_URL=https://sistema.fenixwireless.com.br
IXC_USERNAME=...
IXC_PASSWORD=...
IXC_BRANCH_NAME="FENIX LITORAL-"
IXC_TECHNICIANS="GUSTAVO BEZZA (LITORAL),CEZAR GUEDES (LITORAL)"
IXC_COMPANY_ID=1   # id da empresa no Operix a que essas OS pertencem

# opcional — ajuste fino
IXC_SYNC_TIMEOUT=90
IXC_CIRCUIT_MAX_FAILURES=3
IXC_CIRCUIT_COOLDOWN_MINUTES=30
```

`IXC_COMPANY_ID` é o `id` da empresa já existente no Operix (não do IXC) — as OS sincronizadas
ficam associadas a ela, com o mesmo isolamento de tenant do resto do sistema.

## Passo a passo

1. Instale o Playwright do scraper (uma vez só, no servidor onde o Laravel roda):
   ```bash
   cd scripts/ixc-sync && npm install && npm run install-browser
   ```
2. Calibre o scraper contra o IXC real seguindo `scripts/ixc-sync/README.md` — isso é obrigatório
   antes do primeiro uso, porque os seletores (`scripts/ixc-sync/config.js`) e a extração de
   campos (`App\Services\Ixc\IxcSyncService::mapExternalRecord()`) foram escritos a partir de
   prints, não de um teste real.
3. Preencha o `.env` do Laravel com as chaves acima.
4. Rode manualmente para conferir: `php artisan ixc:sync`.
5. Confira a página **IXC** no menu (permissão `ixc.view` — já concedida a admin/manager/
   dispatcher) — as OS devem aparecer agrupadas por técnico.
6. Só então defina `IXC_SYNC_ENABLED=true` para o agendamento automático assumir.

## Circuit breaker

Depois de `IXC_CIRCUIT_MAX_FAILURES` falhas seguidas (padrão 3), o `ixc:sync` para de tentar por
`IXC_CIRCUIT_COOLDOWN_MINUTES` (padrão 30) — a mensagem de erro do comando avisa até quando.
Depois de corrigir o problema (selector, credencial, etc.), destrave na hora:

```bash
php artisan ixc:sync --reset-circuit
```

## O que é sincronizado

Tabela `ixc_service_orders` (somente leitura do lado Operix — nunca escreve de volta no IXC):
OS não agendadas e agendadas, filtradas por filial e pela lista de técnicos configurada.
`raw_payload` guarda o registro bruto que o scraper capturou, então um campo que a extração
inicial não reconheceu ainda pode ser recuperado sem rodar o scraper de novo.
