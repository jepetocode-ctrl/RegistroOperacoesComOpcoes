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


## Etapa 7 — Calculadoras (parcial; não declarar concluída)

- Modificada `resources/views/dashboard/index.php` para adicionar abas Black-Scholes-Merton e strike reverso na dashboard autenticada.
- A tela permite editar os parâmetros de preço do ativo, strike (Black-Scholes), prazo, volatilidade, taxa livre de risco, dividend yield e preço-alvo (strike reverso).
- A precificação contempla call/put europeia, prazo zero e volatilidade zero. O strike reverso usa busca por bisseção e informa falhas de solução; a grade de strikes listados é opcional, explicitamente fornecida pelo usuário e separada do strike teórico.
- **Pendente crítico:** o cálculo está temporariamente inline em JavaScript na view, não integrado ao motor TypeScript. A árvore do repositório contém `src/financial-engine/`, com `package.json`, `tsconfig.json` e testes TypeScript; o pacote raiz não configura esse build. Ainda é necessário executar `npm test` dentro de `src/financial-engine` no checkout real e integrar a view ao artefato compilado.
- Não há evidência de teste numérico automatizado nem de verificação manual no navegador. Etapa 7 permanece em andamento.

### Próxima ação recomendada para Etapa 7

Inventariar a árvore real do repositório, localizar o motor TypeScript e sua estratégia de build. Extrair Black-Scholes e strike reverso para esse módulo, acrescentar testes de referência e limites, conectar a view ao artefato efetivamente compilado e executar a suíte PHP e testes do motor no checkout. Verificar também a acessibilidade e erros no navegador.


## Etapa 8 — Interface e dashboard (implementação inicial; validação pendente)

- Atualizada `resources/views/dashboard/index.php` com layout responsivo, navegação lateral e seções de visão geral, posições, lotes em aberto e calculadoras preservadas.
- Indicadores e tabelas consomem `GET /api/positions`, `GET /api/option-contracts` e `GET /api/assets`; os dados não são semeados na interface. O painel usa apenas um endpoint de posições para evitar duplicar os mesmos resultados retornados também por `GET /api/operations`.
- Adicionados filtros por código/estado da posição e por contrato/lado do lote, gráfico comparativo por contrato, alertas de ausência de cotação e vencimentos próximos, estados de carregamento, mensagens de erro e botão para atualizar.
- Resultado não realizado aparece como indisponível se algum contrato aberto não tiver cotação; fluxo de caixa é rotulado separadamente de lucro.
- **Não declarar Etapa 8 concluída ainda:** não foi possível executar a suíte PHP nem validar a tela em navegador nesta sessão. É necessário testar os indicadores com operações de exemplo controladas, verificar responsividade real, erros HTTP/sessão e os casos de carteira vazia/sem cotação. A Etapa 7 também continua parcial.
