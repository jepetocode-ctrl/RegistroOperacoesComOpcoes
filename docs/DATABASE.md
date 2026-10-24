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
- 2026_10_24_000002_create_structured_strategy_domain.php: catálogo/modelos, slots, posições estruturadas, vínculos ao ledger e conversão compatível de estratégias legadas.

## Tabelas de operações

- operations: execução de compra/venda, fluxo bruto e custos.
- operation_closures: quantidade alocada de cada abertura para cada encerramento e resultado realizado.
- market_quotes: histórico de cotações por contrato e usuário.
- operation_history: trilha append-only de ações de operação.
- strategy_templates / strategy_template_slots: modelos do catálogo e modelos próprios, com slots normalizados e restrições.
- structured_positions / structured_position_legs: instâncias agrupadas e vínculos das operações de abertura/rolagem aos slots.

## Integridade

Operações estruturadas continuam em `operations` e usam `structured_position_id`; seus encerramentos permanecem em `operation_closures`. A migração mantém `strategies` e seu conteúdo original. JSON legado ilegível é copiado para `legacy_legs` e marcado para revisão, sem descartar o valor de origem.

As rotinas de aplicação validam titularidade do ativo, contrato, modelo e posição. Chaves estrangeiras preservam referências. Consultas e mutações financeiras sempre usam o user_id autenticado.
