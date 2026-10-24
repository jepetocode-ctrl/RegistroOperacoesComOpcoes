# Status do projeto

## Estado atual

A base do projeto está em um estado híbrido, funcional e validado em partes, mas ainda não finalizado como conversão completa para Laravel.

### O que está confirmado

- bootstrap Laravel 12 validado
- `php artisan route:list --except-vendor` funciona
- `curl http://127.0.0.1:8020/health` retorna `status=ok`
- migrações Laravel executam corretamente em SQLite temporário
- motor financeiro em TypeScript passou em 8 testes de payoff, reconhecimento e Black-Scholes

### O que ainda não está concluído

- cadastro de estratégia multi-perna completo pela UI
- edição/reabertura real de posições pela interface
- persistência formal de `strategy_legs` em fluxo de cadastro web finalizado
- migração total de autenticação e sessão para Laravel
- conversão final de todos os controllers de legado para o framework

## Degradações e riscos

- os endpoints legado e o Laravel convivem; este é um estado de transição e não de remoção final do PHP puro
- `strategy_legs` e `legs` em JSON podem divergir se a gravação não for centralizada
- validação da UI web e fluxo de edição ainda precisa ser montada sobre a interface final

## Verificação executada

```bash
composer test
npm test --prefix src/financial-engine
DB_CONNECTION=sqlite DB_DATABASE=/tmp/registro-laravel-migration-fixed.sqlite php artisan migrate --force
curl -sS -o /tmp/laravel-health-retry.json -w 'health_http=%{http_code}\n' http://127.0.0.1:8020/health
```

Resultados reais:

- `composer test`: `OK (18 tests, 73 assertions)`
- `npm test --prefix src/financial-engine`: `8 tests passed, 0 failed`
- `php artisan migrate --force`: sucess full on SQLite temp database
- health endpoint: `HTTP 200`

## Próximo passo realista

Completar a camada de cadastro/edit/visualização em Laravel e standardizar a escrita de pernas em `strategy_legs`, mantendo compatibilidade com o JSON legado.
