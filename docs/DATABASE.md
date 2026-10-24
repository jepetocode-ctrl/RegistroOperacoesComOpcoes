# Banco de dados

## Motor principal

PostgreSQL é o banco principal; SQLite é usado no desenvolvimento/testes quando configurado.

## Convenções

- Persistência financeira em NUMERIC/DECIMAL.
- Migrações versionadas em database/migrations/.
- Operações registradas como ledger; encerramentos são alocações referenciando abertura e fechamento.
- Operação, alocações e histórico persistidos em transação única.
- Cotações são registros históricos; posições usam a cotação mais recente por contrato e usuário.

## Migrações

- 20261023_000001_create_app_base_tables.php: usuários, configurações e auditoria.
- 20261023_000002_create_financial_registrations.php: ativos, contratos e estratégias.
- 20261023_000003_create_operations_and_positions.php: operações, alocações de encerramento, cotações e histórico.
- 2026_10_24_000002_create_structured_strategy_domain.php: modelos de estratégia, slots, posições estruturadas e vínculo de operações ao grupo.

## Tabelas de operações

- operations: execução de compra/venda, fluxo bruto e custos.
- operation_closures: quantidade alocada de cada abertura para cada encerramento e resultado realizado.
- market_quotes: histórico de cotações por contrato e usuário.
- operation_history: trilha append-only de ações de operação.
- strategy_templates / strategy_template_slots: modelos reutilizáveis e respectivas pernas.
- structured_positions / structured_position_legs: agrupamentos de operações que formam cada boleta multi-pernas.

## Integridade

As rotinas de aplicação validam titularidade do ativo, contrato, estratégia e posição. Operações estruturadas continuam em `operations`, ligadas por `structured_position_id`; alocações de fechamento seguem em `operation_closures`. Chaves estrangeiras e transações preservam referências. Consultas e mutações financeiras usam o user_id autenticado.
