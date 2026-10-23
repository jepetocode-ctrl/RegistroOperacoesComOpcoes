# Próximos passos

## Etapa atual

Etapa 6 — Rolagens implementada inicialmente, mas não validada. A Etapa 5 também aguarda execução da suíte e validação dos bancos.

## Prioridade imediata

1. Executar `composer test`, incluindo os testes de rolagem.
2. Executar `php scripts/run-migrations.php` em SQLite e PostgreSQL 16.
3. Validar rollback quando falhar a gravação de cada perna, da alocação, do registro da rolagem e do histórico.
4. Validar rolagens parciais sucessivas, múltiplas aberturas e isolamento por usuário.
5. Revisar alocação proporcional de custos e política de arredondamento em operações parciais.
6. Definir controle de concorrência para evitar duas rolagens consumirem a mesma quantidade remanescente.
7. Integrar endpoints de rolagem à interface HTML/CSS/JavaScript.

## Critérios de aceitação da Etapa 6

- Identificador UUID único por rolagem.
- Vínculo rastreável com operação de origem, perna de encerramento, perna de abertura e estratégia.
- Crédito/débito líquido e custos armazenados separadamente.
- Histórico preservado e consultável por usuário e identificador.
- Crédito líquido não é classificado automaticamente como lucro; resultado realizado continua separado.
- Quantidade remanescente da operação de origem respeitada e posição nova criada pela perna de abertura.
- Operações, alocação, registro da rolagem e histórico persistidos em uma transação; falha não deixa registros parciais.
- Contratos, estratégia e operação de origem sempre validados no escopo do usuário autenticado.
- Testes automatizados e migrações executados com sucesso em SQLite e PostgreSQL antes de marcar a etapa concluída.

## Observação

O código foi adicionado pelo conector GitHub, sem ambiente de execução disponível nesta sessão. Os testes e migrações ainda não foram executados.
