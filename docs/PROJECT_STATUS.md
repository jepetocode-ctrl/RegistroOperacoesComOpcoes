# Status do projeto

## Etapa atual

Etapa 6 — Rolagens: implementação inicial registrada; execução dos testes e validação PostgreSQL/SQLite ainda pendentes. A Etapa 5 também permanece com validação pendente conforme os registros anteriores.

## Base existente

- Autenticação/sessão PHP e endpoints de cadastros financeiros.
- Migrações para ativos, contratos de opções e estratégias.
- Motor financeiro TypeScript separado do backend.
- Ledger de operações, alocações de encerramento, posições e histórico.
- Registro inicial de rolagens com duas pernas e histórico.

## Etapa 6 — implementação adicionada

- Migração `20261023_000004_create_rollovers.php` para identidade UUID única, vínculo à estratégia, operação de origem, pernas de encerramento/abertura, custos, fluxo líquido e histórico.
- Serviço `RolloverService` que registra as duas pernas, alocação de encerramento, histórico e registro da rolagem em transação única.
- Validação de usuário proprietário da operação de origem, contratos ativos e estratégia do usuário.
- Validação da quantidade remanescente disponível na operação de origem.
- Rotas autenticadas `GET /api/rollovers`, `GET /api/rollovers/{uuid}` e `POST /api/rollovers`; mutação protegida por CSRF.
- Testes de integração para rastreabilidade, cálculo do fluxo líquido, quantidade remanescente e rollback forçado na gravação do histórico.

## Arquivos adicionados/modificados

- Adicionado: `database/migrations/20261023_000004_create_rollovers.php`
- Adicionado: `app/Services/RolloverService.php`
- Adicionado: `app/Controllers/RolloverController.php`
- Adicionado: `tests/Feature/RolloverServiceTest.php`
- Modificado: `routes/api.php`
- Modificado: `README.md`
- Modificado: `docs/ARCHITECTURE.md`
- Modificado: `docs/PROJECT_STATUS.md`
- Modificado: `docs/NEXT_STEPS.md`
- Modificado: `docs/DECISIONS.md`

## Verificação

Não foi possível executar `composer test`, `php scripts/run-migrations.php` nem testes contra PostgreSQL a partir da conexão GitHub disponível nesta sessão. Os testes foram adicionados, mas não foram executados. Não declarar a Etapa 5 ou a Etapa 6 concluída até rodar os comandos no checkout e corrigir eventuais falhas.

## Pendências e riscos

- Executar a suíte PHPUnit incluindo `RolloverServiceTest`.
- Executar todas as migrações em SQLite e PostgreSQL real.
- Revisar a semântica de custo de entrada alocado em encerramentos parciais: o cálculo da Etapa 5 já tem política pendente de revisão.
- Confirmar comportamento de concorrência para rolagens simultâneas sobre a mesma operação de origem; considerar bloqueio de linha PostgreSQL e estratégia compatível com SQLite.
- A interface ainda precisa integrar os endpoints de rolagem.
- O serviço cria a perna de encerramento referenciando a operação original e a perna de abertura referenciando o encerramento; validar com testes a atualização de posições para múltiplas rolagens sucessivas.
- O cálculo de resultado realizado do encerramento da rolagem deve ser revisado junto à política de alocação de custos já pendente na Etapa 5.

## Próximo passo recomendado

Executar `composer test` e `php scripts/run-migrations.php`, corrigir falhas e validar PostgreSQL 16. Priorizar teste de rollback por falha em cada ponto de gravação e concorrência ao encerrar a mesma posição.
