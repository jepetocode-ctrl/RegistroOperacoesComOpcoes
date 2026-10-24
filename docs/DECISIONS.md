# Decisões técnicas

## 1. Laravel como camada moderna, mas sem apagar legado

Foi adotada a estratégia de manter o código legado em funcionamento e acrescentar o Laravel 12 como bootstrap compatível. Isso reduz risco de regressão durante a migração.

## 2. Compatibilidade de schema e dados primeiro

A aplicação mantém as migrações existentes e adiciona `strategy_templates`, `strategy_template_slots`, `structured_positions` e `structured_position_legs`. O JSON legado é preservado; conteúdos ilegíveis são marcados para revisão.

## 3. Modelo canônico de perna

A perna foi padronizada com `CanonicalOptionLeg` em código. Isso estabelece:

- convenção de sinais por `BUY` / `SELL`
- campos monetários em custo e premium
- `quantity * multiplier` como unidade da perna
- `expirationDate` como critério de validade da estratégia

## 4. Reconhecimento explícito e não por inferência

O motor financeiro faz reconhecimento somente quando a estrutura combina com regras bem definidas. Quando não reconhece a combinação, responde com `UNRECOGNIZED` ou `UNSUPPORTED`, nunca com resultado enganoso.

## 5. Payoff determinístico sob vencimento

A lógica PHP implementada calcula exposição aberta no vencimento para opções de vencimento comum e retorna indisponível para vencimentos diferentes ou ausência de pernas abertas. Reconhecimento explícito cobre um subconjunto do catálogo; outros modelos podem ser registrados sem reconhecimento garantido.

O motor TypeScript permanece independente e não é chamado por este fluxo. A paridade entre implementações ainda precisa ser estabelecida.

## 6. Testes como fonte de verdade

O projeto é validado por testes PHPUnit, testes do motor TypeScript e migrações em SQLite temporário.

## 7. Estado final desta decisão

Ainda não é correto declarar a migração completa para Laravel terminada. A UI e a persistência estruturada estão integradas ao runtime híbrido; a migração nativa, o suporte ao ledger de ativos e a paridade dos motores financeiros são etapas distintas.
