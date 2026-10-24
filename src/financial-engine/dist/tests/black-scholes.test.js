import test from 'node:test';
import assert from 'node:assert/strict';
import { analyzeStrategyPayoff, blackScholes, findReverseStrike } from '../src/index.js';
function optionLeg(overrides = {}) {
    return {
        instrumentType: 'OPTION',
        side: 'BUY',
        optionType: 'CALL',
        strike: 100,
        expirationDate: '2027-01-15',
        quantity: 1,
        premium: 0,
        multiplier: 100,
        costs: 0,
        ...overrides,
    };
}
test('BS call returns ITM classification and positive extrinsic value', () => {
    const input = {
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
    const input = {
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
        type: 'CALL'
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
test('long call payoff includes entry debit, quantity, multiplier and costs', () => {
    const result = analyzeStrategyPayoff({
        legs: [optionLeg({ quantity: 2, premium: 3, costs: 20 })],
        scenarioPrices: [0, 103.1, 120],
    });
    assert.equal(result.recognition.strategy, 'LONG_CALL');
    assert.equal(result.initialCashFlow, -600);
    assert.equal(result.netInitialCashFlow, -620);
    assert.equal(result.breakEvenPoints[0], 103.1);
    assert.equal(result.maxProfit, null);
    assert.equal(result.maxLoss, 620);
    assert.equal(result.scenarios[0].netProfit, -620);
    assert.ok(Math.abs(result.scenarios[1].netProfit) < 1e-8);
    assert.equal(result.scenarios[2].netProfit, 3380);
});
test('bull call spread calculates capped payoff and exact break-even', () => {
    const result = analyzeStrategyPayoff({
        legs: [
            optionLeg({ side: 'BUY', strike: 100, premium: 8, costs: 10 }),
            optionLeg({ side: 'SELL', strike: 110, premium: 3 }),
        ],
        scenarioPrices: [0, 105.1, 110, 120],
    });
    assert.equal(result.recognition.strategy, 'BULL_CALL_SPREAD');
    assert.equal(result.netInitialCashFlow, -510);
    assert.deepEqual(result.breakEvenPoints, [105.1]);
    assert.equal(result.maxProfit, 490);
    assert.equal(result.maxLoss, 510);
    assert.equal(result.scenarios[0].netProfit, -510);
    assert.ok(Math.abs(result.scenarios[1].netProfit) < 1e-8);
    assert.deepEqual(result.scenarios.slice(2).map((scenario) => scenario.netProfit), [490, 490]);
});
test('unrecognized same-expiration combinations retain valid calculated payoff', () => {
    const result = analyzeStrategyPayoff({
        legs: [
            optionLeg({ side: 'BUY', strike: 100, premium: 5 }),
            optionLeg({ side: 'BUY', strike: 110, premium: 2 }),
        ],
        scenarioPrices: [120],
    });
    assert.equal(result.supported, true);
    assert.equal(result.recognition.status, 'UNRECOGNIZED');
    assert.equal(result.recognition.strategy, null);
    assert.equal(result.scenarios[0].netProfit, 2300);
});
test('mixed expirations are explicitly unsupported and return no payoff scenarios', () => {
    const result = analyzeStrategyPayoff({
        legs: [
            optionLeg({ expirationDate: '2027-01-15' }),
            optionLeg({ optionType: 'PUT', strike: 95, expirationDate: '2027-02-19' }),
        ],
    });
    assert.equal(result.supported, false);
    assert.equal(result.recognition.status, 'UNSUPPORTED');
    assert.match(result.unsupportedReason ?? '', /vencimentos diferentes/);
    assert.deepEqual(result.scenarios, []);
});
test('invalid leg units are rejected before payoff calculation', () => {
    assert.throws(() => analyzeStrategyPayoff({ legs: [optionLeg({ quantity: 0 })] }), /quantity must be a positive integer/);
});
