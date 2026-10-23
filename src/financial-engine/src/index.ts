export type OptionType = 'CALL' | 'PUT';
export type OptionClassification = 'ITM' | 'ATM' | 'OTM';

export interface BlackScholesInput {
  spot: number;
  strike: number;
  volatility: number;
  rate: number;
  dividendYield: number;
  timeToMaturity: number;
  type: OptionType;
}

export interface BlackScholesResult {
  price: number;
  intrinsicValue: number;
  extrinsicValue: number;
  delta: number;
  gamma: number;
  theta: number;
  vega: number;
  rho: number;
  riskNeutralProbabilityITM: number;
  classification: OptionClassification;
  percentageDistanceFromStrike: number;
  d1: number;
  d2: number;
}

export interface ReverseStrikeInput {
  spot: number;
  targetPrice: number;
  volatility: number;
  rate: number;
  dividendYield: number;
  timeToMaturity: number;
  type: OptionType;
  lowerBound: number;
  upperBound: number;
  tolerance?: number;
  maxIterations?: number;
}

export interface ReverseStrikeResult {
  strike: number;
  recalculatedPrice: number;
  converged: boolean;
  absoluteError: number;
  relativeError: number;
  iterations: number;
  nearestListedStrike: number;
  status: 'OK' | 'NO_SOLUTION' | 'MAX_ITERATIONS';
}

const EPSILON = 1e-12;

export const engineStatus = 'Financial engine scaffold ready';

function clampProbability(value: number): number {
  return Math.min(1, Math.max(0, value));
}

function normalCdf(x: number): number {
  const a1 = 0.254829592;
  const a2 = -0.284496736;
  const a3 = 1.421413741;
  const a4 = -1.453152027;
  const a5 = 1.061405429;
  const p = 0.3275911;

  const sign = x < 0 ? -1 : 1;
  const absX = Math.abs(x);
  const t = 1 / (1 + p * absX);
  const erf = 1 - (((((a5 * t + a4) * t + a3) * t + a2) * t + a1) * t * Math.exp(-absX * absX));
  return 0.5 * (1 + sign * erf);
}

function normalPdf(x: number): number {
  return Math.exp(-(x * x) / 2) / Math.sqrt(2 * Math.PI);
}

function ensureValidInput(input: BlackScholesInput): void {
  if (!Number.isFinite(input.spot) || input.spot <= 0) {
    throw new Error('Spot must be a positive finite number.');
  }
  if (!Number.isFinite(input.strike) || input.strike <= 0) {
    throw new Error('Strike must be a positive finite number.');
  }
  if (!Number.isFinite(input.volatility) || input.volatility < 0) {
    throw new Error('Volatility must be a finite non-negative number.');
  }
  if (!Number.isFinite(input.rate)) {
    throw new Error('Rate must be finite.');
  }
  if (!Number.isFinite(input.dividendYield) || input.dividendYield < 0) {
    throw new Error('Dividend yield must be a finite non-negative number.');
  }
  if (!Number.isFinite(input.timeToMaturity) || input.timeToMaturity < 0) {
    throw new Error('Time to maturity must be a finite non-negative number.');
  }
}

function classifyOption(spot: number, strike: number, type: OptionType): OptionClassification {
  const distance = (spot - strike) / strike;
  if (Math.abs(distance) < 1e-6) {
    return 'ATM';
  }
  return type === 'CALL' ? (spot > strike ? 'ITM' : 'OTM') : (spot < strike ? 'ITM' : 'OTM');
}

export function blackScholes(input: BlackScholesInput): BlackScholesResult {
  ensureValidInput(input);

  const { spot, strike, volatility, rate, dividendYield, timeToMaturity, type } = input;

  if (timeToMaturity === 0) {
    const intrinsic = type === 'CALL' ? Math.max(spot - strike, 0) : Math.max(strike - spot, 0);
    return {
      price: intrinsic,
      intrinsicValue: intrinsic,
      extrinsicValue: 0,
      delta: 0,
      gamma: 0,
      theta: 0,
      vega: 0,
      rho: 0,
      riskNeutralProbabilityITM: type === 'CALL' ? (spot >= strike ? 1 : 0) : (spot <= strike ? 1 : 0),
      classification: classifyOption(spot, strike, type),
      percentageDistanceFromStrike: (spot - strike) / strike,
      d1: 0,
      d2: 0,
    };
  }

  const sigmaSqrtT = volatility * Math.sqrt(timeToMaturity);

  if (sigmaSqrtT <= EPSILON) {
    const intrinsic = type === 'CALL'
      ? Math.max(spot * Math.exp(-dividendYield * timeToMaturity) - strike * Math.exp(-rate * timeToMaturity), 0)
      : Math.max(strike * Math.exp(-rate * timeToMaturity) - spot * Math.exp(-dividendYield * timeToMaturity), 0);

    return {
      price: intrinsic,
      intrinsicValue: intrinsic,
      extrinsicValue: 0,
      delta: type === 'CALL' ? 1 : 0,
      gamma: 0,
      theta: 0,
      vega: 0,
      rho: type === 'CALL'
        ? strike * timeToMaturity * Math.exp(-rate * timeToMaturity)
        : -strike * timeToMaturity * Math.exp(-rate * timeToMaturity),
      riskNeutralProbabilityITM: clampProbability(type === 'CALL' ? (spot >= strike ? 1 : 0) : (spot <= strike ? 1 : 0)),
      classification: classifyOption(spot, strike, type),
      percentageDistanceFromStrike: (spot - strike) / strike,
      d1: 0,
      d2: 0,
    };
  }

  const d1 = (Math.log(spot / strike) + (rate - dividendYield + (volatility ** 2) / 2) * timeToMaturity) / sigmaSqrtT;
  const d2 = d1 - sigmaSqrtT;

  const discountedSpot = spot * Math.exp(-dividendYield * timeToMaturity);
  const discountedStrike = strike * Math.exp(-rate * timeToMaturity);

  const callPrice = discountedSpot * normalCdf(d1) - discountedStrike * normalCdf(d2);
  const putPrice = discountedStrike * normalCdf(-d2) - discountedSpot * normalCdf(-d1);

  const price = type === 'CALL' ? callPrice : putPrice;
  const intrinsic = type === 'CALL' ? Math.max(spot - strike, 0) : Math.max(strike - spot, 0);
  const extrinsic = Math.max(price - intrinsic, 0);

  const delta = type === 'CALL'
    ? Math.exp(-dividendYield * timeToMaturity) * normalCdf(d1)
    : -Math.exp(-dividendYield * timeToMaturity) * normalCdf(-d1);

  const gamma = Math.exp(-dividendYield * timeToMaturity) * normalPdf(d1) / (spot * volatility * Math.sqrt(timeToMaturity));

  const theta = type === 'CALL'
    ? (-(spot * Math.exp(-dividendYield * timeToMaturity) * normalPdf(d1) * volatility) / (2 * Math.sqrt(timeToMaturity)))
      - rate * strike * Math.exp(-rate * timeToMaturity) * normalCdf(d2)
      + dividendYield * spot * Math.exp(-dividendYield * timeToMaturity) * normalCdf(d1)
    : (-(spot * Math.exp(-dividendYield * timeToMaturity) * normalPdf(d1) * volatility) / (2 * Math.sqrt(timeToMaturity)))
      + rate * strike * Math.exp(-rate * timeToMaturity) * normalCdf(-d2)
      - dividendYield * spot * Math.exp(-dividendYield * timeToMaturity) * normalCdf(-d1);

  const vega = spot * Math.exp(-dividendYield * timeToMaturity) * normalPdf(d1) * Math.sqrt(timeToMaturity);

  const rho = type === 'CALL'
    ? strike * timeToMaturity * Math.exp(-rate * timeToMaturity) * normalCdf(d2)
    : -strike * timeToMaturity * Math.exp(-rate * timeToMaturity) * normalCdf(-d2);

  const riskNeutralProbabilityITM = clampProbability(type === 'CALL' ? normalCdf(d2) : normalCdf(-d2));

  return {
    price,
    intrinsicValue: intrinsic,
    extrinsicValue: extrinsic,
    delta,
    gamma,
    theta,
    vega,
    rho,
    riskNeutralProbabilityITM,
    classification: classifyOption(spot, strike, type),
    percentageDistanceFromStrike: (spot - strike) / strike,
    d1,
    d2,
  };
}

export function findReverseStrike(input: ReverseStrikeInput): ReverseStrikeResult {
  const tolerance = input.tolerance ?? 1e-6;
  const maxIterations = input.maxIterations ?? 200;
  const lower = Math.max(input.lowerBound, Number.EPSILON);
  const upper = Math.max(input.upperBound, lower + 1e-6);

  const target = input.targetPrice;

  if (!Number.isFinite(target) || target < 0) {
    throw new Error('Target price must be a finite non-negative number.');
  }

  const lowPrice = blackScholes({
    spot: input.spot,
    strike: lower,
    volatility: input.volatility,
    rate: input.rate,
    dividendYield: input.dividendYield,
    timeToMaturity: input.timeToMaturity,
    type: input.type,
  }).price;

  const highPrice = blackScholes({
    spot: input.spot,
    strike: upper,
    volatility: input.volatility,
    rate: input.rate,
    dividendYield: input.dividendYield,
    timeToMaturity: input.timeToMaturity,
    type: input.type,
  }).price;

  if ((lowPrice - target) * (highPrice - target) > 0) {
    return {
      strike: (lower + upper) / 2,
      recalculatedPrice: blackScholes({
        spot: input.spot,
        strike: (lower + upper) / 2,
        volatility: input.volatility,
        rate: input.rate,
        dividendYield: input.dividendYield,
        timeToMaturity: input.timeToMaturity,
        type: input.type,
      }).price,
      converged: false,
      absoluteError: Number.POSITIVE_INFINITY,
      relativeError: Number.POSITIVE_INFINITY,
      iterations: 0,
      nearestListedStrike: (lower + upper) / 2,
      status: 'NO_SOLUTION',
    };
  }

  let low = lower;
  let high = upper;
  let currentStrike = (low + high) / 2;
  let iterations = 0;

  for (; iterations < maxIterations; iterations += 1) {
    const currentPrice = blackScholes({
      spot: input.spot,
      strike: currentStrike,
      volatility: input.volatility,
      rate: input.rate,
      dividendYield: input.dividendYield,
      timeToMaturity: input.timeToMaturity,
      type: input.type,
    }).price;

    const absoluteError = Math.abs(currentPrice - target);
    const relativeError = target === 0 ? absoluteError : absoluteError / target;

    if (absoluteError <= tolerance || relativeError <= tolerance) {
      return {
        strike: currentStrike,
        recalculatedPrice: currentPrice,
        converged: true,
        absoluteError,
        relativeError,
        iterations: iterations + 1,
        nearestListedStrike: currentStrike,
        status: 'OK',
      };
    }

    const lowPriceCurrent = blackScholes({
      spot: input.spot,
      strike: low,
      volatility: input.volatility,
      rate: input.rate,
      dividendYield: input.dividendYield,
      timeToMaturity: input.timeToMaturity,
      type: input.type,
    }).price;

    const highPriceCurrent = blackScholes({
      spot: input.spot,
      strike: high,
      volatility: input.volatility,
      rate: input.rate,
      dividendYield: input.dividendYield,
      timeToMaturity: input.timeToMaturity,
      type: input.type,
    }).price;

    if ((lowPriceCurrent - target) * (currentPrice - target) <= 0) {
      high = currentStrike;
    } else {
      low = currentStrike;
    }

    currentStrike = (low + high) / 2;
  }

  const finalPrice = blackScholes({
    spot: input.spot,
    strike: currentStrike,
    volatility: input.volatility,
    rate: input.rate,
    dividendYield: input.dividendYield,
    timeToMaturity: input.timeToMaturity,
    type: input.type,
  }).price;

  return {
    strike: currentStrike,
    recalculatedPrice: finalPrice,
    converged: false,
    absoluteError: Math.abs(finalPrice - target),
    relativeError: target === 0 ? Math.abs(finalPrice - target) : Math.abs(finalPrice - target) / target,
    iterations,
    nearestListedStrike: currentStrike,
    status: iterations >= maxIterations ? 'MAX_ITERATIONS' : 'NO_SOLUTION',
  };
}
