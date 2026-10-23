# Status do projeto

## Etapa atual

Etapa 7/8 — núcleo financeiro e dashboard: o motor TypeScript foi validado por testes numéricos; a dashboard autenticada está implementada com consumo de API real, mas a integração final da calculadora ao motor e a validação em navegador continuam pendentes.

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


## Etapa 7 — Calculadoras (núcleo validado; integração visual pendente)

- Modificada `resources/views/dashboard/index.php` para adicionar abas Black-Scholes-Merton e strike reverso na dashboard autenticada.
- A tela permite editar os parâmetros de preço do ativo, strike (Black-Scholes), prazo, volatilidade, taxa livre de risco, dividend yield e preço-alvo (strike reverso).
- A precificação contempla call/put europeia, prazo zero e volatilidade zero. O strike reverso usa busca por bisseção e informa falhas de solução; a grade de strikes listados é opcional, explicitamente fornecida pelo usuário e separada do strike teórico.
- **Validação executada:** `cd src/financial-engine && npm test` passou com `3` testes aprovados e `0` falhas.
- **Pendente arquitetural:** a lógica ainda está parcialmente duplicada em JavaScript inline na view; a integração final da dashboard com o módulo TypeScript precisa ser concluída para remover duplicação e garantir um único ponto de verdade numérico.

### Próxima ação recomendada para Etapa 7

Concluir a integração da view ao módulo TypeScript em `src/financial-engine`, evitando duplicação do cálculo, e confirmar no navegador os casos de erro e de fronteira numérica.


## Etapa 8 — Interface e dashboard (estrutura validada, UX pendente)

- Atualizada `resources/views/dashboard/index.php` com layout responsivo, navegação lateral e seções de visão geral, posições, lotes em aberto e calculadoras preservadas.
- Indicadores e tabelas consomem `GET /api/positions`, `GET /api/option-contracts` e `GET /api/assets`; os dados não são semeados na interface. O painel usa apenas um endpoint de posições para evitar duplicar os mesmos resultados retornados também por `GET /api/operations`.
- Adicionados filtros por código/estado da posição e por contrato/lado do lote, gráfico comparativo por contrato, alertas de ausência de cotação e vencimentos próximos, estados de carregamento, mensagens de erro e botão para atualizar.
- Resultado não realizado aparece como indisponível se algum contrato aberto não tiver cotação; fluxo de caixa é rotulado separadamente de lucro.
- **Status atual:** a estrutura da dashboard está implementada e a aplicação PHP continua validada, mas a checagem de navegador, responsividade e UX real ainda não foi executada neste ambiente; por isso a Etapa 8 deve ser tratada como parcialmente validada e não finalizada.
