# API

Todas as rotas financeiras exigem sessão autenticada. Para POST/PUT/PATCH/DELETE, envie o token CSRF da sessão no campo _csrf_token.

## Cadastros

Recursos: assets, option-contracts, strategies.

- GET /api/{recurso} e GET /api/{recurso}/{id}
- POST /api/{recurso}
- PUT /api/{recurso}/{id} ou PATCH /api/{recurso}/{id}
- DELETE /api/{recurso}/{id} arquiva ativos e contratos, alterando o status para INACTIVE sem apagar operações relacionadas.
- Ativos, contratos e estratégias só podem ser consultados e utilizados pelo usuário proprietário.

## Boleta multi-pernas

- GET/POST `/api/strategy-templates`
- GET/PUT `/api/strategy-templates/{id}`
- POST `/api/strategy-templates/{id}/clone`
- GET/POST `/api/structured-positions`
- GET `/api/structured-positions/{uuid}`
- PUT `/api/structured-positions/{uuid}` (somente posição aberta sem encerramento ou rolagem)
- DELETE `/api/structured-positions/{uuid}` (arquiva sem remover operações ou histórico)
- POST `/api/structured-positions/{uuid}/close`
- POST `/api/structured-positions/{uuid}/roll`
- GET `/api/unstructured-operations`

O endpoint de registro recebe `strategy_template_id`, `asset_id`, `base_quantity`, `executed_at`, `general_costs` e `legs`. Cada perna deve conter `slot_key`, `option_contract_id`, `unit_price` e `costs`. Contratos, ativo, tipo da opção, strikes, vencimentos e proporções são validados pelo servidor; todas as pernas são gravadas numa transação. Se a classificação divergir ou não for reconhecida, a API pede confirmação explícita antes de persistir.

A tela `/registros` oferece a boleta agrupada e prévia de débito, crédito, custos, fluxo líquido e payoff máximo estimado no vencimento. O payoff é indisponível para vencimentos diferentes. No dashboard, as operações aparecem em árvore por ativo-objeto, com estratégias agrupadas, posições por contrato e lotes abertos; ações de edição e arquivamento preservam o ledger. O prêmio unitário é exibido em cada perna e lote. Posições abertas sem encerramento/rolagem podem ser editadas mantendo o histórico das operações no ledger; posições com atividade posterior devem ser ajustadas por encerramento ou rolagem. Modelos próprios podem ser editados enquanto não estiverem vinculados a posições; para os demais, crie uma cópia e edite a nova versão. Custos gerais são distribuídos entre as pernas em proporção à quantidade de contratos e multiplicadores; custos informados por perna ficam atribuídos àquela perna.

## Operações e posições

- POST /api/operations: registra compra/venda.
- PUT /api/operations/{id}: edita operação isolada sem fechamentos/rolagens relacionados.
- DELETE /api/operations/{id}: arquiva lote isolado, preservando histórico; operações com relacionamentos devem ser ajustadas no fluxo agrupado.
- GET /api/operations: resumo de posições calculadas a partir do ledger.
- GET /api/operations/{id}/history: histórico da operação autenticada.
- GET /api/positions: posições por contrato.
- GET /api/positions/{contract_id}: posições filtradas por contrato.
- POST /api/quotes: registra cotação para contrato do próprio usuário.

Exemplo de operação:

    {"option_contract_id":12,"side":"BUY","quantity":10,"unit_price":1.25,"costs":0.50,"executed_at":"2026-10-23 10:30:00","operation_kind":"OPEN"}

Campos obrigatórios: option_contract_id, side (BUY/SELL), quantity (inteiro positivo), unit_price (não negativo). costs é opcional e não negativo. operation_kind aceita TRADE, OPEN, CLOSE, PARTIAL_CLOSE, REVERSAL ou ADJUSTMENT.

CLOSE e PARTIAL_CLOSE exigem quantidade integralmente disponível. REVERSAL exige operação no sentido contrário e quantidade maior que a posição líquida aberta; a parte necessária encerra a posição e o excedente abre a posição inversa. TRADE compensa a posição oposta antes de abrir o excedente. OPEN não reduz uma posição oposta. Uma perna estruturada não pode ser encerrada por um lançamento avulso; use o fluxo agrupado para encerramento. Rolagens por perna preservam o vínculo com a posição estruturada.

O fluxo de caixa bruto é -preço × quantidade × multiplicador para BUY e positivo para SELL; custos ficam em campo separado. Resultado realizado é apurado por alocação de encerramentos e desconta custos de entrada/saída atribuídos. Resultado não realizado é null quando não há cotação; com cotação, usa as quantidades abertas e o preço de marcação.

## Respostas e segurança

Sucesso usa {"success":true,"data":...}. Validação retorna HTTP 422, sessão ausente 401, recurso não encontrado 404 e falha CSRF 419. As consultas são sempre escopadas pelo usuário da sessão.

## Administração de usuários

Somente administradores podem acessar `/admin/users`. A tela permite bloquear/desbloquear contas e redefinir a senha por meio de uma senha temporária, que é exibida uma única vez e exige troca no próximo login. A exclusão é lógica: marca a conta como `DELETED`, impede novos acessos e preserva os dados financeiros. Não é permitido bloquear ou excluir a própria conta, nem remover o último administrador ativo. As ações administrativas exigem token CSRF.
