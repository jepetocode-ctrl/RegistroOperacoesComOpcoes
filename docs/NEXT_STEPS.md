# Próximos passos

## Etapa atual

Etapa 5 — Operações e posições.

## Prioridade imediata

1. Executar composer test e php scripts/run-migrations.php.
2. Validar a suíte em PostgreSQL e SQLite.
3. Adicionar testes para inversão, cotação e resultado não realizado, múltiplas aberturas FIFO e rollback após falha na gravação de histórico/alocações.
4. Revisar a alocação de custos e política de arredondamento em operações parciais sucessivas.
5. Definir fluxo compensatório de correção/cancelamento sem mutar o ledger financeiro.
6. Integrar os endpoints a uma interface HTML/CSS/JavaScript.

## Critérios de aceitação

- Quantidades abertas calculadas a partir de operações e alocações de encerramento.
- Encerramento parcial/total não excede a posição disponível.
- Inversão explícita validada e consistente.
- Fluxo financeiro bruto, custos, resultado realizado e resultado não realizado apresentados separadamente.
- Registro de operação, alocações e histórico gravados em transação única.
- Dados sempre escopados pelo usuário autenticado.

## Observação

A implementação inicial está registrada, mas a etapa permanece não validada até que testes automatizados e migrações sejam executados nos bancos suportados.
