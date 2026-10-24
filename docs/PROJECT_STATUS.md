# Status do projeto

## Estado atual

A aplicação permanece híbrida, mas agora possui um fluxo integrado de modelos e posições estruturadas sobre o ledger existente.

### O que está confirmado

- bootstrap Laravel 12 validado
- `php artisan route:list --except-vendor` funciona
- `curl http://127.0.0.1:8020/health` retorna `status=ok`
- migrações Laravel executam corretamente em SQLite temporário
- motor financeiro em TypeScript passou em 8 testes de payoff, reconhecimento e Black-Scholes

### Implementações concluídas neste fluxo

- builder visual de modelos com criação, edição, clonagem e slots configuráveis
- registro transacional de posições e validação de propriedade, proporções e restrições
- dashboard agrupada, visualização de pernas e ações de encerramento/rolagem em nível de posição
- persistência de modelos, slots, instâncias e auditoria, preservando vínculo ao ledger
- operações estruturadas protegidas contra edição ou encerramento por fluxos avulsos
- catálogo com suporte explícito e payoff indisponível para cenários não suportados

### O que ainda não está concluído

- migração total de autenticação e sessão para Laravel
- conversão final de todos os controllers de legado para o framework
- paridade automatizada/runtime entre payoff PHP e motor TypeScript
- ledger para pernas de ativo-objeto e payoff com vencimentos diferentes
- validação visual automatizada em navegador para todos os fluxos

## Degradações e riscos

- os endpoints legado e o Laravel convivem; este é um estado de transição e não de remoção final do PHP puro
- existem duas implementações de cálculo financeiro
- ações estruturadas devem ser realizadas pela própria posição, e não por operações avulsas

## Comandos de validação

```bash
composer test
npm test --prefix src/financial-engine
DB_CONNECTION=sqlite DB_DATABASE=/tmp/registro-laravel-migration-fixed.sqlite php artisan migrate --force
curl -sS -o /tmp/laravel-health-retry.json -w 'health_http=%{http_code}\n' http://127.0.0.1:8020/health
```

- `composer test`
- `npm test --prefix src/financial-engine`
- `php artisan route:list --except-vendor`
- migrações completas em SQLite temporário
