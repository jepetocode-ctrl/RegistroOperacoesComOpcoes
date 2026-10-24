# API

Todas as rotas financeiras exigem sessão autenticada. Para POST/PUT/PATCH, envie o token CSRF da sessão no campo _csrf_token.

## Cadastros

Recursos: assets, option-contracts, strategies.

- GET /api/{recurso} e GET /api/{recurso}/{id}
- POST /api/{recurso}
- PUT /api/{recurso}/{id} ou PATCH /api/{recurso}/{id}
- Desativação lógica por status INACTIVE.
- Ativos, contratos e estratégias têm escopo por usuário; um usuário não pode registrar operações com contratos de outra conta.

## Modelos e posições estruturadas

- GET/POST `/api/strategy-templates`
- GET/PUT `/api/strategy-templates/{id}`
- POST `/api/strategy-templates/{id}/clone`
- GET/POST `/api/structured-positions`
- GET `/api/structured-positions/{uuid}`
- POST `/api/structured-positions/{uuid}/close`
- POST `/api/structured-positions/{uuid}/roll`
- GET `/api/unstructured-operations`

Modelos contêm slots com `slot_key`, instrumento, lado, tipo de opção, proporção e restrições. Modelos do catálogo podem ser clonados; modelos legados com conteúdo inválido ficam disponíveis para revisão, mas não podem ser usados antes de corrigidos.

O registro de posição recebe `strategy_template_id`, `asset_id`, `base_quantity`, `executed_at`, `currency`, `broker`, `general_costs` e uma lista `legs`. Cada item inclui `slot_key`, `option_contract_id`, `unit_price` e custos opcionais. O backend verifica propriedade dos dados, ativo, tipo de opção, quantidade proporcional e restrições do modelo antes de criar qualquer operação.

Se a classificação inferida não for reconhecida ou divergir do modelo, o endpoint retorna HTTP 409 e exige `confirm_classification: true` para persistir. O registro grava todas as pernas, vínculos e trilha de auditoria em uma transação.

Encerramento e rolagem recebem `lots` (lotes-base) e uma lista `legs` com `slot_key`, preço unitário e custos; rolagem também requer `option_contract_id` para cada novo contrato. A posição só pode ser fechada/rolada enquanto as pernas estiverem alinhadas em lotes completos. O fluxo das rolagens é caixa, não lucro automático.

O catálogo limita algumas estruturas a registro e classificação. Payoff é indisponível para vencimentos diferentes e para pernas de ativo-objeto; nenhum preço, volatilidade ou contrato de mercado é presumido pelo catálogo.

## Operações e posições

- POST /api/operations: registra compra/venda.
- GET /api/operations: resumo de posições calculadas a partir do ledger.
- GET /api/operations/{id}/history: histórico da operação autenticada.
- GET /api/positions: posições por contrato.
- GET /api/positions/{contract_id}: posições filtradas por contrato.
- POST /api/quotes: registra cotação para contrato do próprio usuário.

Exemplo de operação:

    {"option_contract_id":12,"side":"BUY","quantity":10,"unit_price":1.25,"costs":0.50,"executed_at":"2026-10-23 10:30:00","operation_kind":"OPEN"}

Campos obrigatórios: option_contract_id, side (BUY/SELL), quantity (inteiro positivo), unit_price (não negativo). costs é opcional e não negativo. operation_kind aceita TRADE, OPEN, CLOSE, PARTIAL_CLOSE, REVERSAL ou ADJUSTMENT.

CLOSE e PARTIAL_CLOSE exigem quantidade integralmente disponível. REVERSAL exige operação no sentido contrário e quantidade maior que a posição líquida aberta; a parte necessária encerra a posição e o excedente abre a posição inversa. Operações ordinárias opostas podem encerrar a posição existente e abrir o excedente no sentido inverso, exceto quando a alocação atingir uma perna estruturada, que deve ser gerida pelas rotas da posição.

O fluxo de caixa bruto é -preço × quantidade × multiplicador para BUY e positivo para SELL; custos ficam em campo separado. Resultado realizado é apurado por alocação de encerramentos e desconta custos de entrada/saída atribuídos. Resultado não realizado é null quando não há cotação; com cotação, usa as quantidades abertas e o preço de marcação.

## Respostas e segurança

Sucesso usa {"success":true,"data":...}. Validação retorna HTTP 422, sessão ausente 401, recurso não encontrado 404 e falha CSRF 419. As consultas são sempre escopadas pelo usuário da sessão.
