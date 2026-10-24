# Regras financeiras

## Convenções implementadas

## Diretrizes previstas

- Black-Scholes-Merton com dividend yield contínuo.
- Dias corridos / 365.
- Valores persistidos em colunas numéricas; entradas são validadas antes da escrita.
- Distinção entre fluxo bruto, custos, resultado realizado e valor de mercado.
- BUY produz débito e SELL produz crédito. Custos permanecem separados e custos gerais são alocados entre pernas.
- Payoff determinístico no vencimento soma o valor intrínseco por perna, quantidade, multiplicador, sinal e custos de entrada. Break-even e limites são retornados para opções com vencimento comum.
- Após encerramento parcial ou rolagem, a exposição no vencimento considera somente as quantidades ainda abertas; o resultado realizado é exibido separadamente e não é somado ao payoff.
- Vencimentos diferentes, ausência de pernas abertas e instrumentos de ativo-objeto não recebem payoff numérico; a limitação é informada explicitamente.

## Importante

O módulo TypeScript em `src/financial-engine` continua fornecendo Black-Scholes-Merton, reconhecimento e payoff em testes próprios. O fluxo PHP de posições estruturadas mantém hoje uma implementação separada de reconhecimento/payoff e não invoca o pacote TypeScript no runtime; a paridade entre as implementações permanece uma lacuna. Resultados são cálculos de modelo, não garantias de preço, execução ou aconselhamento financeiro.
