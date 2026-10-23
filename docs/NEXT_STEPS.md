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


## Etapa 7 — Calculadoras (em andamento)

Foi adicionada uma primeira interface em `resources/views/dashboard/index.php` com formulário Black-Scholes-Merton e strike reverso. A grade de strikes listados só é usada quando fornecida pelo usuário; strike teórico e listado são apresentados separadamente.

### Pendências obrigatórias antes de concluir

1. Localizar/inventariar o motor TypeScript real e o processo de build; migrar os cálculos para o motor tipado, sem manter duplicação financeira na view.
2. Criar testes automatizados de preço call/put com valores de referência, paridade put-call, limites de prazo zero, volatilidade zero, taxa/dividend yield e strike reverso com preço-alvo dentro/fora do domínio.
3. Definir e testar tolerância numérica, precisão da CDF normal e comportamento quando há múltiplas soluções/ausência de solução.
4. Testar no navegador a validação dos campos, mensagens acessíveis e responsividade.
5. Executar `composer test`, testes do motor e migrações aplicáveis no checkout real; registrar apenas resultados efetivamente observados.

A implementação atual é parcial. O motor TypeScript está em `src/financial-engine/` e possui scripts `build` e `test`, mas não houve execução dos testes nesta sessão nem integração da view com o artefato compilado. Não marcar Etapa 7 como concluída.


## Etapa 8 — Interface e dashboard (em andamento)

1. Executar `composer test` e os testes do motor financeiro no checkout; corrigir regressões encontradas.
2. Validar em navegador desktop e celular o layout, navegação, filtros, acessibilidade, carregamento e mensagens de erro.
3. Conferir indicadores com cenários controlados: carteira vazia, posição aberta com/sem cotação, encerramento parcial, múltiplos contratos e rolagem.
4. Confirmar que realizado é somado uma vez por contrato e que fluxo de caixa não é rotulado como lucro; documentar a definição financeira dos KPIs.
5. Revisar o gráfico para resultados positivos/negativos e garantir que o sentido dos valores esteja visualmente claro.
6. Integrar a calculadora à engine TypeScript e concluir as pendências da Etapa 7 antes de considerar o conjunto da interface validado.

A implementação inicial usa dados dos endpoints existentes e não cria preços fictícios. Testes de execução e navegador ainda não foram feitos nesta sessão; não marcar a etapa como concluída.
