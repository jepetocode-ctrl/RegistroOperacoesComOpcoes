# MAPA_DO_SISTEMA — RegistroOperacoesComOpcoes

## Estado verificado

Fatos confirmados no repositório atual:

- O projeto era originalmente um app PHP puro com middleware próprio, roteamento em arrays, PDO e um motor TypeScript separado.
- O projeto agora também possui um bootstrap Laravel 12 (`artisan`, `bootstrap/app.php`, `public/index.php`).
- O Laravel foi integrado como camada compatível, preservando os controladores e roteamento legados por meio das rotas `legacy_web.php` e `legacy_api.php`.
- O banco preserva migrações legadas e adiciona modelos, slots, posições estruturadas e vínculos ao ledger.
- `/registros` oferece o builder de modelos/posições; `/dashboard` agrupa posições, pernas e ações estruturais.
- O motor TypeScript continua separado; o backend PHP possui implementação própria de payoff para posições estruturadas.

## Arquitetura atual

```text
HTTP -> Laravel bootstrap -> route list -> legacy bridge -> App\Controllers (legado)
        |-> database/migrations -> App\Database\LegacyMigrations
        \-> src/financial-engine -> BS/payoff/recognition library
        \-> PHP PositionPayoff -> deterministic structured-position payoff
```

## Domínio financeiro atual

- `CanonicalOptionLeg` usa:
  - `instrumentType`: `OPTION`
  - `side`: `BUY` / `SELL`
  - `optionType`: `CALL` / `PUT`
  - `strike`
  - `expirationDate`
  - `quantity`
  - `premium`
  - `multiplier`
  - `costs`
- Convenções de sinal:
  - `BUY` -> fluxo de entrada negativo de caixa bruto (débito)
  - `SELL` -> fluxo de entrada positivo de caixa bruto (crédito)
  - payoffs de opções são derivados em relação ao preço do ativo no vencimento
- Unidade: quantidade × multiplicador = notional da perna
- Os payoffs são calculados por perna e consolidados em soma; isso é validado por testes de motor

## O que ainda não está concluído

- autenticação e vários controllers continuam no adaptador legado/PDO
- templates com pernas de ativo-objeto ainda não são registráveis pelo ledger
- payoff multi-expiração não é calculado
- paridade de resultados entre TypeScript e PHP ainda não está automatizada

## Testes e validação

Executados com sucesso:

- `composer test`
- `npm test --prefix src/financial-engine`
- `DB_CONNECTION=sqlite DB_DATABASE=/tmp/registro-laravel-migration-fixed.sqlite php artisan migrate --force`
- `php artisan route:list --except-vendor`

## Conclusão

O fluxo de posições estruturadas está integrado ao runtime híbrido e ao ledger, com testes para operações centrais. Isso não representa conversão integral para Laravel nem cobertura de todas as estratégias possíveis.
