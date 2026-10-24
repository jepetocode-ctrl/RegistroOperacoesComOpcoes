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

export type TradeSide = 'BUY' | 'SELL';

export interface CanonicalOptionLeg {
  instrumentType: 'OPTION';
  contractId?: number;
  side: TradeSide;
  optionType: OptionType;
  strike: number;
  expirationDate: string;
  quantity: number;
  premium: number;
  multiplier: number;
  costs: number;
}

export interface StrategyPayoffInput {
  legs: CanonicalOptionLeg[];
  scenarioPrices?: number[];
}

export type RecognizedStrategy =
  | 'LONG_CALL' | 'SHORT_CALL' | 'LONG_PUT' | 'SHORT_PUT'
  | 'BULL_CALL_SPREAD' | 'BEAR_CALL_SPREAD' | 'BULL_PUT_SPREAD' | 'BEAR_PUT_SPREAD'
  | 'LONG_STRADDLE' | 'SHORT_STRADDLE' | 'LONG_STRANGLE' | 'SHORT_STRANGLE'
  | 'LONG_CALL_BUTTERFLY' | 'SHORT_CALL_BUTTERFLY'
  | 'LONG_PUT_BUTTERFLY' | 'SHORT_PUT_BUTTERFLY'
  | 'IRON_CONDOR';

export interface StrategyRecognitionResult {
  status: 'RECOGNIZED' | 'UNRECOGNIZED' | 'UNSUPPORTED';
  strategy: RecognizedStrategy | null;
  reason: string | null;
}

export interface StrategyScenario {
  underlyingPrice: number;
  optionPayoff: number;
  netProfit: number;
}

export interface StrategyPayoffResult {
  supported: boolean;
  unsupportedReason: string | null;
  expirationDate: string | null;
  recognition: StrategyRecognitionResult;
  initialCashFlow: number;
  totalCosts: number;
  netInitialCashFlow: number;
  breakEvenPoints: number[];
  maxProfit: number | null;
  maxLoss: number | null;
  scenarios: StrategyScenario[];
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

const PAYOFF_EPSILON = 1e-8;

function sideSign(side: TradeSide): number {
  return side === 'BUY' ? 1 : -1;
}

function legNotional(leg: CanonicalOptionLeg): number {
  return leg.quantity * leg.multiplier;
}

function validatePayoffLegs(legs: CanonicalOptionLeg[]): void {
  if (!Array.isArray(legs) || legs.length === 0) {
    throw new Error('At least one option leg is required.');
  }

  legs.forEach((leg, index) => {
    const prefix = `Leg ${index + 1}`;
    if (leg.instrumentType !== 'OPTION') throw new Error(`${prefix}: only option legs are supported.`);
    if (leg.side !== 'BUY' && leg.side !== 'SELL') throw new Error(`${prefix}: side must be BUY or SELL.`);
    if (leg.optionType !== 'CALL' && leg.optionType !== 'PUT') throw new Error(`${prefix}: option type must be CALL or PUT.`);
    if (!Number.isFinite(leg.strike) || leg.strike <= 0) throw new Error(`${prefix}: strike must be positive.`);
    if (!/^\d{4}-\d{2}-\d{2}$/.test(leg.expirationDate) || Number.isNaN(Date.parse(`${leg.expirationDate}T00:00:00Z`))) {
      throw new Error(`${prefix}: expiration date must use YYYY-MM-DD.`);
    }
    if (!Number.isInteger(leg.quantity) || leg.quantity < 1) throw new Error(`${prefix}: quantity must be a positive integer.`);
    if (!Number.isFinite(leg.premium) || leg.premium < 0) throw new Error(`${prefix}: premium must be non-negative.`);
    if (!Number.isInteger(leg.multiplier) || leg.multiplier < 1) throw new Error(`${prefix}: multiplier must be a positive integer.`);
    if (!Number.isFinite(leg.costs) || leg.costs < 0) throw new Error(`${prefix}: costs must be non-negative.`);
  });
}

function recognizeStrategy(legs: CanonicalOptionLeg[]): StrategyRecognitionResult {
  if (legs.length === 1) {
    const leg = legs[0];
    const strategy = `${leg.side === 'BUY' ? 'LONG' : 'SHORT'}_${leg.optionType}` as RecognizedStrategy;
    return { status: 'RECOGNIZED', strategy, reason: null };
  }

  const equalNotional = legs.every((leg) => Math.abs(legNotional(leg) - legNotional(legs[0])) < PAYOFF_EPSILON);
  if (legs.length === 2 && equalNotional) {
    const call = legs.find((leg) => leg.optionType === 'CALL');
    const put = legs.find((leg) => leg.optionType === 'PUT');
    if (call && put && call.side === put.side) {
      if (Math.abs(call.strike - put.strike) < PAYOFF_EPSILON) {
        return { status: 'RECOGNIZED', strategy: call.side === 'BUY' ? 'LONG_STRADDLE' : 'SHORT_STRADDLE', reason: null };
      }
      if (put.strike < call.strike) {
        return { status: 'RECOGNIZED', strategy: call.side === 'BUY' ? 'LONG_STRANGLE' : 'SHORT_STRANGLE', reason: null };
      }
    }

    if (legs[0].optionType === legs[1].optionType && legs[0].side !== legs[1].side && Math.abs(legs[0].strike - legs[1].strike) >= PAYOFF_EPSILON) {
      const [low, high] = [...legs].sort((a, b) => a.strike - b.strike);
      if (low.optionType === 'CALL') {
        return { status: 'RECOGNIZED', strategy: low.side === 'BUY' ? 'BULL_CALL_SPREAD' : 'BEAR_CALL_SPREAD', reason: null };
      }
      return { status: 'RECOGNIZED', strategy: low.side === 'BUY' ? 'BULL_PUT_SPREAD' : 'BEAR_PUT_SPREAD', reason: null };
    }
  }

  if (legs.length === 3 && legs.every((leg) => leg.optionType === legs[0].optionType && leg.multiplier === legs[0].multiplier)) {
    const sorted = [...legs].sort((a, b) => a.strike - b.strike);
    const equalWings = Math.abs((sorted[1].strike - sorted[0].strike) - (sorted[2].strike - sorted[1].strike)) < PAYOFF_EPSILON;
    const oneTwoOne = Math.abs(sorted[0].quantity - sorted[2].quantity) < PAYOFF_EPSILON
      && Math.abs(sorted[1].quantity - 2 * sorted[0].quantity) < PAYOFF_EPSILON;
    if (equalWings && oneTwoOne && sorted[0].side === sorted[2].side && sorted[1].side !== sorted[0].side) {
      const direction = sorted[0].side === 'BUY' ? 'LONG' : 'SHORT';
      return { status: 'RECOGNIZED', strategy: `${direction}_${legs[0].optionType}_BUTTERFLY` as RecognizedStrategy, reason: null };
    }
  }

  if (legs.length === 4 && equalNotional) {
    const putLegs = legs.filter((leg) => leg.optionType === 'PUT').sort((a, b) => a.strike - b.strike);
    const callLegs = legs.filter((leg) => leg.optionType === 'CALL').sort((a, b) => a.strike - b.strike);
    if (putLegs.length === 2 && callLegs.length === 2
      && putLegs[0].strike < putLegs[1].strike
      && putLegs[1].strike < callLegs[0].strike
      && callLegs[0].strike < callLegs[1].strike
      && putLegs[0].side === 'BUY' && putLegs[1].side === 'SELL'
      && callLegs[0].side === 'SELL' && callLegs[1].side === 'BUY') {
      return { status: 'RECOGNIZED', strategy: 'IRON_CONDOR', reason: null };
    }
  }

  return {
    status: 'UNRECOGNIZED',
    strategy: null,
    reason: 'Payoff suportado, mas a combinação não corresponde às regras explícitas de reconhecimento.',
  };
}

function legPayoffAtExpiry(leg: CanonicalOptionLeg, underlyingPrice: number): number {
  const intrinsic = leg.optionType === 'CALL'
    ? Math.max(underlyingPrice - leg.strike, 0)
    : Math.max(leg.strike - underlyingPrice, 0);
  return sideSign(leg.side) * intrinsic * legNotional(leg);
}

function uniqueSorted(values: number[]): number[] {
  return [...new Set(values.map((value) => Math.max(0, value)))].sort((a, b) => a - b);
}

function calculateBreakEvens(legs: CanonicalOptionLeg[], netInitialCashFlow: number): number[] {
  const strikes = uniqueSorted([0, ...legs.map((leg) => leg.strike)]);
  const profitAt = (price: number): number => netInitialCashFlow + legs.reduce((sum, leg) => sum + legPayoffAtExpiry(leg, price), 0);
  const points: number[] = [];

  for (let index = 0; index < strikes.length; index += 1) {
    const low = strikes[index];
    const lowProfit = profitAt(low);
    if (Math.abs(lowProfit) < PAYOFF_EPSILON) points.push(low);
    if (index === strikes.length - 1) continue;

    const high = strikes[index + 1];
    const highProfit = profitAt(high);
    if (lowProfit * highProfit < 0) {
      points.push(low + ((-lowProfit) * (high - low)) / (highProfit - lowProfit));
    }
  }

  const lastStrike = strikes[strikes.length - 1];
  const tailSlope = legs.reduce((sum, leg) => sum + (leg.optionType === 'CALL' ? sideSign(leg.side) * legNotional(leg) : 0), 0);
  const lastProfit = profitAt(lastStrike);
  if (Math.abs(tailSlope) >= PAYOFF_EPSILON) {
    const tailRoot = lastStrike - lastProfit / tailSlope;
    if (tailRoot > lastStrike + PAYOFF_EPSILON) points.push(tailRoot);
  }

  return uniqueSorted(points).filter((point, index, all) => index === 0 || Math.abs(point - all[index - 1]) >= PAYOFF_EPSILON);
}

function defaultScenarioPrices(legs: CanonicalOptionLeg[]): number[] {
  const strikes = uniqueSorted(legs.map((leg) => leg.strike));
  const prices = [0, ...strikes];
  strikes.forEach((strike) => prices.push(Math.max(0, strike * 0.9), strike * 1.1));
  prices.push(strikes[strikes.length - 1] * 1.5);
  return uniqueSorted(prices);
}

export function analyzeStrategyPayoff(input: StrategyPayoffInput): StrategyPayoffResult {
  validatePayoffLegs(input.legs);
  const legs = input.legs;
  const expirations = [...new Set(legs.map((leg) => leg.expirationDate))];
  const initialCashFlow = legs.reduce((sum, leg) => sum - sideSign(leg.side) * leg.premium * legNotional(leg), 0);
  const totalCosts = legs.reduce((sum, leg) => sum + leg.costs, 0);
  const netInitialCashFlow = initialCashFlow - totalCosts;

  if (expirations.length !== 1) {
    const reason = 'Payoff com vencimentos diferentes depende da trajetória temporal e não é calculado por este modelo.';
    return {
      supported: false,
      unsupportedReason: reason,
      expirationDate: null,
      recognition: { status: 'UNSUPPORTED', strategy: null, reason },
      initialCashFlow,
      totalCosts,
      netInitialCashFlow,
      breakEvenPoints: [],
      maxProfit: null,
      maxLoss: null,
      scenarios: [],
    };
  }

  const recognition = recognizeStrategy(legs);
  const breakEvenPoints = calculateBreakEvens(legs, netInitialCashFlow);
  const boundaryPrices = uniqueSorted([0, ...legs.map((leg) => leg.strike)]);
  const boundaryProfits = boundaryPrices.map((price) => netInitialCashFlow + legs.reduce((sum, leg) => sum + legPayoffAtExpiry(leg, price), 0));
  const tailSlope = legs.reduce((sum, leg) => sum + (leg.optionType === 'CALL' ? sideSign(leg.side) * legNotional(leg) : 0), 0);
  const unboundedProfit = tailSlope > PAYOFF_EPSILON;
  const unboundedLoss = tailSlope < -PAYOFF_EPSILON;
  const requestedPrices = input.scenarioPrices ?? defaultScenarioPrices(legs);

  requestedPrices.forEach((price) => {
    if (!Number.isFinite(price) || price < 0) throw new Error('Scenario underlying prices must be finite and non-negative.');
  });

  return {
    supported: true,
    unsupportedReason: null,
    expirationDate: expirations[0],
    recognition,
    initialCashFlow,
    totalCosts,
    netInitialCashFlow,
    breakEvenPoints,
    maxProfit: unboundedProfit ? null : Math.max(0, ...boundaryProfits),
    maxLoss: unboundedLoss ? null : Math.max(0, -Math.min(...boundaryProfits)),
    scenarios: uniqueSorted(requestedPrices).map((underlyingPrice) => {
      const optionPayoff = legs.reduce((sum, leg) => sum + legPayoffAtExpiry(leg, underlyingPrice), 0);
      return { underlyingPrice, optionPayoff, netProfit: netInitialCashFlow + optionPayoff };
    }),
  };
}
