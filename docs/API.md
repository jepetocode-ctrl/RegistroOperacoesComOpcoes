# API

Todas as rotas financeiras exigem sessão autenticada. Para POST/PUT/PATCH, envie o token CSRF da sessão no campo _csrf_token.

## Cadastros

Recursos: assets, option-contracts, strategies.

- GET /api/{recurso} e GET /api/{recurso}/{id}
- POST /api/{recurso}
- PUT /api/{recurso}/{id} ou PATCH /api/{recurso}/{id}
- Desativação lógica por status INACTIVE.

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

CLOSE e PARTIAL_CLOSE exigem quantidade integralmente disponível. REVERSAL exige quantidade igual à posição líquida aberta. Operações ordinárias opostas podem encerrar a posição existente e abrir o excedente no sentido inverso.

O fluxo de caixa bruto é -preço × quantidade × multiplicador para BUY e positivo para SELL; custos ficam em campo separado. Resultado realizado é apurado por alocação de encerramentos e desconta custos de entrada/saída atribuídos. Resultado não realizado é null quando não há cotação; com cotação, usa as quantidades abertas e o preço de marcação.

## Respostas e segurança

Sucesso usa {"success":true,"data":...}. Validação retorna HTTP 422, sessão ausente 401, recurso não encontrado 404 e falha CSRF 419. As consultas são sempre escopadas pelo usuário da sessão.
