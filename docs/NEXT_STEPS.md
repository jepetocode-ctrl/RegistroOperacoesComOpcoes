# Próximos passos

## Etapa atual

Etapa 6 — Rolagens validada no checkout atual. A base funcional de transação de encerramento + abertura + histórico foi validada pela suíte PHPUnit e pela migração de schema.

## Prioridade imediata

1. Confirmar comportamento de concorrência em rolagens simultâneas sobre a mesma operação de origem.
2. Validar rolagens parciais sucessivas, múltiplas aberturas e isolamento por usuário em cenários mais complexos.
3. Revisar alocação proporcional de custos e política de arredondamento em operações parciais.
4. Definir controle de correção/compensação para rolagens já registradas, sem mutar o ledger histórico.
5. Integrar endpoints de rolagem à interface HTML/CSS/JavaScript.

## Critérios de aceitação da Etapa 6

- Identificador UUID único por rolagem. ✅
- Vínculo rastreável com operação de origem, perna de encerramento, perna de abertura e estratégia. ✅
- Crédito/débito líquido e custos armazenados separadamente. ✅
- Histórico preservado e consultável por usuário e identificador. ✅
- Crédito líquido não é classificado automaticamente como lucro; resultado realizado continua separado. ✅
- Quantidade remanescente da operação de origem respeitada e posição nova criada pela perna de abertura. ✅
- Operações, alocação, registro da rolagem e histórico persistidos em uma transação; falha não deixa registros parciais. ✅
- Contratos, estratégia e operação de origem sempre validados no escopo do usuário autenticado. ✅
- Testes automatizados e migrações executados com sucesso no checkout atual. ✅

## Observação

A Etapa 6 foi validada no ambiente atual com `composer test` e `php scripts/run-migrations.php`; as pendências restantes continuam sendo refinamentos de concorrência, UX e regras de custo mais complexas.
