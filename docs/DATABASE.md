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

## Tabelas de operações

- operations: execução de compra/venda, fluxo bruto e custos.
- operation_closures: quantidade alocada de cada abertura para cada encerramento e resultado realizado.
- market_quotes: histórico de cotações por contrato e usuário.
- operation_history: trilha append-only de ações de operação.

## Integridade

As rotinas de aplicação validam titularidade do contrato e estratégia. Chaves estrangeiras preservam referências. Consultas e mutações financeiras sempre usam o user_id autenticado.
