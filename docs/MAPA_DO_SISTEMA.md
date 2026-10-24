# MAPA_DO_SISTEMA — RegistroOperacoesComOpcoes

## Estado verificado

Fatos confirmados no repositório atual:

- O projeto era originalmente um app PHP puro com middleware próprio, roteamento em arrays, PDO e um motor TypeScript separado.
- O projeto agora também possui um bootstrap Laravel 12 (`artisan`, `bootstrap/app.php`, `public/index.php`).
- O Laravel foi integrado como camada compatível, preservando os controladores e roteamento legados por meio das rotas `legacy_web.php` e `legacy_api.php`.
- O banco ainda preserva as migrações legadas e adicionou uma tabela `strategy_legs` para estrutura normalizada.
- O motor financeiro em `src/financial-engine` passou a cobrir payoff determinístico em vencimento, reconhecimento explícito e break-even.

## Arquitetura atual

```text
HTTP -> Laravel bootstrap -> route list -> legacy bridge -> App\Controllers (legado)
        |-> database/migrations -> App\Database\LegacyMigrations
        \-> src/financial-engine -> payoff/recognition/BS logic
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

- não há interface completa de cadastro multi-perna em Laravel
- não há edição/reabertura final de posições na UI
- não há persistência de `strategy_legs` unificada em todos os fluxos de aplicação
- não há camada de estratégia/portfolio em Laravel com read/write completo

## Testes e validação

Executados com sucesso:

- `composer test` -> 18 testes, 73 assertions
- `npm test --prefix src/financial-engine` -> 8 testes, 0 failures
- `DB_CONNECTION=sqlite DB_DATABASE=/tmp/registro-laravel-migration-fixed.sqlite php artisan migrate --force` -> migrations executadas com sucesso
- `curl ... /health` -> HTTP 200, `status=ok`

## Conclusão

O projeto não está totalmente convertido para Laravel nem concluído end-to-end. Já está em um estado intermediário com o bootstrap do framework e o motor financeiro avançado, mas ainda exige migração em camadas da UI, autenticação e persistência estruturada para atingir os critérios do pedido original.
