# RegistroOperacoesComOpcoes

Sistema para registro, análise e simulação de operações com opções.

## Status real do projeto

O projeto foi atualizado para um bootstrap Laravel 12 compatível com a base PHP já existente, sem apagar os serviços antigos nem a regra de negócio de persistência em PDO. A compatibilidade foi preservada por meio de:

- bootstrap do Laravel em `bootstrap/app.php` e `public/index.php`;
- roteamento legado em `routes/legacy_web.php` e `routes/legacy_api.php`;
- migrações Laravel com wrappers por `App\Database\LegacyMigrations`;
- tabela extra `strategy_legs` para normalizar pernas em formato estruturado;
- motor financeiro com regra determinística de payoff e reconhecimento em `src/financial-engine`.

Ainda não foi concluída a migração total da interface de cadastro para Laravel e também não é correto afirmar que o cadastro de estratégia multi-perna com edição completa, persistência final e reabertura UI estão finalizados.

## Implementações confirmadas

- Laravel 12 instalado e bootstrap validado por `php artisan route:list`.
- Rota HTTP health respondendo com HTTP 200 em ambiente local.
- Migrações Laravel executando com sucesso em SQLite temporário.
- Estratégia canônica de pernas com `instrumentType`, `side`, `optionType`, `strike`, `expirationDate`, `quantity`, `premium`, `multiplier`, `costs`.
- Payoff em vencimento para estratégias determinísticas com suporte explícito de `LONG_CALL`, `SHORT_CALL`, `LONG_PUT`, `SHORT_PUT`, spreads e straddles/strangles/iron condor parcialmente implementados.
- Regras explícitas de reconhecimento e rejeição de combinações com vencimentos diferentes.
- Testes de motor: 8 testes passados, 0 falhas.

## Limitações atuais

- Cadastro multi-perna pela interface web ainda não foi reescrito em toda a camada Laravel;
- edição/reabertura com persistência em tabela estruturada ainda exige chaining com view/controller novos;
- integração entre UI e backend Laravel para uma estratégia completa ainda é trabalho pendente;
- não é correto afirmar que todas as estruturas complexas de payoff foram implementadas.

## Requisitos do runtime

- PHP 8.3+
- Composer 2+
- SQLite para desenvolvimento/testes
- PostgreSQL como destino de produção (com compatibilidade preservada)

## Execução

```bash
composer install
cp .env.example .env
php artisan key:generate --force
php artisan migrate --force
php artisan route:list --except-vendor
npm test --prefix src/financial-engine
```

## Validação executada

Comandos executados com sucesso:

```bash
composer test
npm test --prefix src/financial-engine
DB_CONNECTION=sqlite DB_DATABASE=/tmp/registro-laravel-migration-fixed.sqlite php artisan migrate --force
curl -sS -o /tmp/laravel-health-retry.json -w 'health_http=%{http_code}\n' http://127.0.0.1:8020/health
```

Resultados reais:

- `composer test` -> `OK (18 tests, 73 assertions)`
- `npm test --prefix src/financial-engine` -> `8 tests passed, 0 failed`
- `php artisan migrate --force` em SQLite temporário -> todas as migrações executadas
- `http://127.0.0.1:8020/health` -> HTTP 200, `status=ok`

## Próximo passo realista

Concluir a camada HTTP/Blade/Laravel das telas de cadastro e edição, migrando os controllers e repositórios de `App\Support\Router` para rotas nativas do framework e gravando as pernas em `strategy_legs` com reabertura/edição real.
