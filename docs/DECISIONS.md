# Decisões técnicas

## 1. Laravel como camada moderna, mas sem apagar legado

Foi adotada a estratégia de manter o código legado em funcionamento e acrescentar o Laravel 12 como bootstrap compatível. Isso reduz risco de regressão durante a migração.

## 2. Compatibilidade de schema primeiro

A aplicação manteve as migrações antiguas funcionando e adicionou uma tabela `strategy_legs` para normalizar pernas sem quebrar referência do JSON legado.

## 3. Modelo canônico de perna

A perna foi padronizada com `CanonicalOptionLeg` em código. Isso estabelece:

- convenção de sinais por `BUY` / `SELL`
- campos monetários em custo e premium
- `quantity * multiplier` como unidade da perna
- `expirationDate` como critério de validade da estratégia

## 4. Reconhecimento explícito e não por inferência

O motor financeiro faz reconhecimento somente quando a estrutura combina com regras bem definidas. Quando não reconhece a combinação, responde com `UNRECOGNIZED` ou `UNSUPPORTED`, nunca com resultado enganoso.

## 5. Payoff determinístico sob vencimento

A lógica implementada cobre `CALL` e `PUT` simples, spreads verticais, straddle/strangle e butterfly em regras explícitas. Estruturas com múltiplos vencimentos continuam sendo rejeitadas por exigirem modelo de trajetória/temporalidade.

## 6. Testes como fonte de verdade

O projeto foi validado com:

- 18 testes PHPUnit
- 8 testes do motor financeiro
- migração Laravel em SQLite temporário

## 7. Estado final desta decisão

Ainda não é correto declarar a migração completa para Laravel terminada. A decisão vigente é migrar em etapas, acelerando a base do framework, a camada de dados e o motor financeiro antes de converter a UI completa.
