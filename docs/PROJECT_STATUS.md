# Status do projeto

## Etapa atual

Etapa 6 — Rolagens: validada com sucesso no checkout atual. A suíte PHPUnit e as migrações foram executadas com resultado positivo em SQLite; a base está pronta para refinamentos de concorrência e interface.

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

Validação executada no checkout atual:

```bash
cd /workspaces/RegistroOperacoesComOpcoes && composer test && php scripts/run-migrations.php
```

Resultado confirmado: `OK (10 tests, 36 assertions)` e migrações até `20261023_000004_create_rollovers.php` executadas com sucesso.

## Pendências e riscos

- Revisar política de concorrência para rolagens simultâneas sobre a mesma operação de origem.
- Validar comportamento de interface e UX dos endpoints de rolagem.
- Revisar alocação proporcional de custos em cenários complexos de múltiplas operações parciais.
- Definir eventual regra de correção/compensação para rolagens já registradas, mantendo o ledger append-only.

## Próximo passo recomendado

Avançar para próxima etapa funcional com foco em integração de interface, refinamento de concorrência e eventual política de correção compensatória, mantendo a transação dura e o ledger imutável.
