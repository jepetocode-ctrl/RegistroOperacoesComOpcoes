# Regras financeiras

## Registro de operações

- Compra (BUY) gera fluxo bruto negativo; venda (SELL), positivo. O fluxo usa preço executado × quantidade × multiplicador do contrato cadastrado.
- `TRADE` compensa a posição oposta antes de abrir eventual excedente.
- `OPEN` só abre/aumenta e é recusado se reduzir lotes existentes.
- `CLOSE` deve encerrar toda a posição líquida; `PARTIAL_CLOSE` encerra uma fração disponível.
- `REVERSAL` precisa ser contrária e maior que a posição aberta; a parte necessária fecha e o excedente abre a posição inversa.
- Encerramentos alocam quantidade entre lotes e reconhecem resultado realizado com custos de entrada/saída rateados.
- Uma perna estruturada só pode ser fechada ou rolada pelas operações vinculadas à posição; não pode ser alterada como lançamento avulso.

## Boletas multi-pernas

- Cada slot valida contrato pertencente ao usuário, ativo-objeto, tipo de opção e restrições de strike/vencimento.
- A quantidade executada por slot é a quantidade-base multiplicada pela proporção do modelo.
- Custos específicos pertencem à perna informada. Custos gerais são distribuídos proporcionalmente a quantidade de contratos × multiplicador; os valores são preservados como custos, sem alterar o fluxo bruto.
- A gravação cria posição, operações, vínculos, histórico e auditoria em transação única.
- O dashboard separa fluxo inicial, resultado realizado, resultado não realizado e exposição das pernas ainda abertas. O payoff de exposição não soma resultado realizado.

## Limitações de cálculo

O motor TypeScript em `src/financial-engine` fornece Black-Scholes e payoff como biblioteca separada. O fluxo atual de posições estruturadas em PHP mantém uma implementação própria e não integra o TypeScript em runtime; paridade precisa de validação automatizada.

Payoff com vencimentos diferentes, posições sem pernas abertas e instrumentos sem suporte no ledger não recebem resultado numérico. Resultados de modelo não garantem preço de mercado, execução ou lucro.
