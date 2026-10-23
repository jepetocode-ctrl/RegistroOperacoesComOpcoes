# Status do projeto

## Etapa atual

Etapa 5 — Operações e posições: implementação inicial registrada; execução dos testes ainda pendente.

## Base existente

- Autenticação/sessão PHP e endpoints de cadastros financeiros.
- Migrações para ativos, contratos de opções e estratégias.
- Motor financeiro TypeScript separado do backend.

## Implementado na Etapa 5

- Ledger de operações de compra/venda com quantidade, preço, custos, fluxo financeiro bruto e data/hora.
- Alocações de encerramento por operação de abertura, com cálculo de resultado realizado líquido dos custos de entrada/saída alocados.
- Validação de encerramento e encerramento parcial para não ultrapassar posição disponível.
- Inversão explícita validada para quantidade igual à posição líquida atual.
- Consulta de posições abertas, fluxo financeiro, custos, resultado realizado e resultado não realizado quando existe cotação.
- Histórico append-only de criação da operação.
- Registro de cotações por contrato.
- Persistência transacional de operação, alocações de encerramento e histórico.
- Rotas API autenticadas e protegidas por CSRF para mutações.

## Arquivos adicionados

- database/migrations/20261023_000003_create_operations_and_positions.php
- app/Repositories/OperationRepository.php
- app/Services/OperationService.php
- app/Controllers/OperationController.php
- tests/Feature/OperationServiceTest.php

## Rotas adicionadas

- GET /api/operations
- POST /api/operations
- GET /api/operations/{id}/history
- GET /api/positions
- GET /api/positions/{id}
- POST /api/quotes

As chamadas exigem sessão autenticada; POST exige token CSRF em _csrf_token.

## Verificação

A suíte PHPUnit e as migrações não foram executadas nesta sessão porque não havia checkout local executável disponível. O teste de regressão foi adicionado, mas ainda não foi executado. Não declarar a etapa concluída até rodar os testes e corrigir eventuais diferenças entre PostgreSQL e SQLite.

## Pendências e riscos

- Executar teste de abertura, fechamento parcial, fechamento excedente, inversão, cotação e isolamento por usuário.
- Validar migração e comportamento no PostgreSQL real e SQLite.
- Verificar arredondamento e alocação de custos em múltiplas aberturas/encerramentos.
- A trilha atual registra criação; edição de operação não é exposta, preservando o ledger append-only.
- Definir política para correção/cancelamento de operações já registradas: preferir lançamento compensatório em vez de alterar registros financeiros históricos.

## Próximo passo recomendado

Executar composer test e php scripts/run-migrations.php, corrigir qualquer falha, e ampliar testes para inversões, cotação e integridade transacional.
