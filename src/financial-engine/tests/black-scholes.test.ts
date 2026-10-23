import test from 'node:test';
import assert from 'node:assert/strict';

import { blackScholes, findReverseStrike, type BlackScholesInput } from '../src/index.js';

test('BS call returns ITM classification and positive extrinsic value', () => {
  const input: BlackScholesInput = {
    spot: 100,
    strike: 95,
    volatility: 0.2,
    rate: 0.05,
    dividendYield: 0.01,
    timeToMaturity: 1,
    type: 'CALL'
  };

  const result = blackScholes(input);

  assert.equal(result.classification, 'ITM');
  assert.ok(result.price > 0);
  assert.ok(result.intrinsicValue > 0);
  assert.ok(result.extrinsicValue >= 0);
  assert.ok(result.delta > 0);
});

test('BS with zero volatility produces degenerate intrinsic value', () => {
  const input: BlackScholesInput = {
    spot: 100,
    strike: 105,
    volatility: 0,
    rate: 0.03,
    dividendYield: 0,
    timeToMaturity: 0.5,
    type: 'CALL'
  };

  const result = blackScholes(input);

  assert.equal(result.classification, 'OTM');
  assert.ok(Math.abs(result.price - 0) < 1e-9);
});

test('reverse strike search recovers the original strike within tolerance', () => {
  const spot = 100;
  const strike = 98;
  const params = {
    spot,
    strike,
    volatility: 0.2,
    rate: 0.05,
    dividendYield: 0.01,
    timeToMaturity: 1,
    type: 'CALL' as const
  };

  const target = blackScholes(params).price;
  const result = findReverseStrike({
    spot,
    targetPrice: target,
    volatility: 0.2,
    rate: 0.05,
    dividendYield: 0.01,
    timeToMaturity: 1,
    type: 'CALL',
    lowerBound: 50,
    upperBound: 150,
    tolerance: 1e-6,
    maxIterations: 200,
  });

  assert.ok(Math.abs(result.strike - strike) < 0.05);
  assert.ok(Math.abs(result.recalculatedPrice - target) < 0.05);
});
