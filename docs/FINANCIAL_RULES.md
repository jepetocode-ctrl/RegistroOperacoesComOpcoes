# Regras financeiras

## Convenção atual

Esta etapa inicial ainda não implementa o motor financeiro completo. A base arquitetural está preparada para separar o núcleo em TypeScript.

## Diretrizes previstas

- Black-Scholes-Merton com dividend yield contínuo.
- Dias corridos / 365.
- Cálculos em tipos numéricos precisos, sem uso de ponto flutuante para persistência.
- Distinção entre fluxo bruto, custos, resultado realizado e valor de mercado.
- Breakeven operacional como métrica do prêmio, nunca como breakeven teórico do ativo.

## Importante

Qualquer cálculo financeiro deve ser tratado no módulo TypeScript e validado no backend antes de persistir registros financeiros.
