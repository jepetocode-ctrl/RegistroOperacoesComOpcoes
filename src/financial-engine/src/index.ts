export type OptionType = 'CALL' | 'PUT';

export interface BlackScholesInput {
  spot: number;
  strike: number;
  volatility: number;
  rate: number;
  dividendYield: number;
  timeToMaturity: number;
  type: OptionType;
}

export const engineStatus = 'Financial engine scaffold ready';
