# IXC Sync (sem API)

A conta do IXC Provedor em uso não tem a API/webservice liberada — só existe login/senha do
painel web (`adm.php`). Este script abre um navegador headless (Playwright), loga com essas
credenciais, aplica os filtros de filial/técnico e captura os dados que a própria tela do IXC
carrega via requisições internas (JSON), em vez de tentar interpretar o HTML renderizado — é
mais estável, mas ainda depende da estrutura da tela do IXC, que eu não pude inspecionar ao
vivo. **Espere precisar calibrar os seletores no primeiro uso.**

## Instalação

```bash
cd scripts/ixc-sync
npm install
npm run install-browser   # baixa o Chromium do Playwright (~150MB, uma vez só)
```

## Calibração (primeira execução)

```bash
cp .env.example .env
# preencha IXC_USERNAME e IXC_PASSWORD no .env — nunca cometa esse arquivo no Git
HEADLESS=0 npm run scrape
```

Com `HEADLESS=0` o navegador abre visível, então dá pra acompanhar se o login, a navegação até
a tela de agendamento e a aplicação dos filtros estão realmente acontecendo. Com `IXC_DEBUG=1`
(já ligado no `.env.example`), toda execução salva em `./debug/`:

- `page.png` — screenshot da tela final
- `page.html` — HTML renderizado
- `captured-responses.json` — **o mais importante**: todas as respostas JSON que a tela do IXC
  recebeu do próprio servidor dela enquanto a página carregava

Se o resultado (`unscheduled`/`scheduled` no JSON de saída) vier vazio, abra
`captured-responses.json` e procure a resposta que contém os dados dos agendamentos/OS — o
formato exato (nomes dos campos) é o que falta ajustar em `extractScheduledFromCapture()` e
`extractUnscheduledFromCapture()` dentro de `scrape.js`. Se os seletores de login/menu/filtro
não baterem (mensagens `[ixc-sync] Could not ...` no terminal), ajuste o bloco `selectors` em
`config.js` — abra a tela real no navegador, aperte F12 e confirme o seletor certo.

## Riscos do lado do IXC — leia antes de ligar em produção

Automatizar o painel de um sistema de terceiro sem API tem riscos reais, mesmo sendo dados da
própria empresa:

- **Conflito de sessão**: se o login usado aqui for o mesmo que um despachante usa no dia a dia,
  cada sincronização pode derrubar a sessão da pessoa que está com o IXC aberto naquele momento.
  **Use um usuário do IXC dedicado só para isso, se o sistema permitir criar mais de um.**
- **Bloqueio por comportamento automatizado**: login repetido em intervalo curto é um padrão
  bem diferente de uso humano — pode acionar proteção antibot/WAF do lado deles.
- **Bloqueio por falha repetida**: se um seletor quebrar e o login começar a falhar, tentar de
  novo sem parar pode travar a conta por excesso de tentativas.
- **Termos de uso**: confirme com quem administra a conta do IXC (ou com a IXCsoft) se automação
  do painel é tolerada — a maioria dos SaaS proíbe isso explicitamente no contrato.

Por isso o comando agendado (`config('ixc.circuit_breaker')`) para sozinho depois de falhas
consecutivas (padrão: 3) por 30 minutos, em vez de insistir a cada ciclo, e o intervalo padrão
é de 10 minutos, não 1-2 — ver `routes/console.php` no projeto Laravel.

## Uso em produção

O Laravel (`App\Services\Ixc\IxcSyncService`, chamado pelo comando agendado `ixc:sync`) executa
`node scrape.js` passando as credenciais como variáveis de ambiente do processo — não é preciso
manter um `.env` neste diretório em produção. Ver `docs/ixc-integration.md` na raiz do projeto
para a configuração completa do lado Laravel.

## Por que não um scraper de HTML puro?

A alternativa mais simples seria interpretar o HTML renderizado (procurar por classes CSS,
texto em posições fixas). Ela quebra a cada ajuste visual que a IXCsoft fizer no produto — e
com um painel de terceiros, updates acontecem sem aviso. Capturar as respostas JSON que a
própria página consome é o mesmo princípio de uma integração por API, só que descoberta em vez
de documentada.
