<?php

declare(strict_types=1);

namespace App\Domain;

final class PositionPayoff
{
    public static function analyze(array $legs): array
    {
        $expirations = array_values(array_unique(array_column($legs, 'expiration_date')));
        $cashFlow = array_sum(array_map(static fn(array $leg): float => (float) $leg['gross_cash_flow'], $legs));
        $costs = array_sum(array_map(static fn(array $leg): float => (float) $leg['costs'], $legs));
        $recognition = self::recognize($legs);
        if (count($expirations) !== 1 || count(array_filter($legs, static fn(array $leg): bool => ($leg['instrument_type'] ?? 'OPTION') !== 'OPTION')) > 0) {
            if ($legs === []) {
                $reason = 'Não há pernas abertas para calcular a exposição no vencimento.';
            } elseif (count($expirations) !== 1) {
                $reason = 'Payoff com vencimentos diferentes depende da trajetória temporal e não é calculado por este modelo.';
            } else {
                $reason = 'O motor atual calcula somente pernas de opções.';
            }
            return [
                'supported' => false,
                'unsupported_reason' => $reason,
                'expiration_date' => null,
                'recognition' => ['status' => 'UNSUPPORTED', 'strategy' => null, 'reason' => $reason],
                'initial_cash_flow' => round($cashFlow, 6),
                'total_costs' => round($costs, 6),
                'net_initial_cash_flow' => round($cashFlow - $costs, 6),
                'break_even_points' => [],
                'max_profit' => null,
                'max_loss' => null,
                'scenarios' => [],
            ];
        }

        $netInitial = $cashFlow - $costs;
        $strikes = array_values(array_unique(array_map(static fn(array $leg): float => (float) $leg['strike_price'], $legs)));
        sort($strikes, SORT_NUMERIC);
        $boundaries = array_values(array_unique(array_merge([0.0], $strikes)));
        $profitAt = static function (float $underlyingPrice) use ($legs, $netInitial): float {
            $profit = $netInitial;
            foreach ($legs as $leg) {
                $intrinsic = $leg['option_type'] === 'CALL'
                    ? max($underlyingPrice - (float) $leg['strike_price'], 0)
                    : max((float) $leg['strike_price'] - $underlyingPrice, 0);
                $direction = $leg['side'] === 'BUY' ? 1 : -1;
                $profit += $direction * $intrinsic * (int) $leg['quantity'] * (int) $leg['contract_multiplier'];
            }
            return $profit;
        };

        $breakEvens = [];
        foreach ($boundaries as $index => $low) {
            $lowProfit = $profitAt($low);
            if (abs($lowProfit) < 0.000001) $breakEvens[] = $low;
            if (!isset($boundaries[$index + 1])) continue;
            $high = $boundaries[$index + 1];
            $highProfit = $profitAt($high);
            if ($lowProfit * $highProfit < 0) {
                $breakEvens[] = $low + ((-$lowProfit) * ($high - $low)) / ($highProfit - $lowProfit);
            }
        }
        $tailSlope = 0.0;
        foreach ($legs as $leg) {
            if ($leg['option_type'] === 'CALL') {
                $tailSlope += ($leg['side'] === 'BUY' ? 1 : -1) * (int) $leg['quantity'] * (int) $leg['contract_multiplier'];
            }
        }
        $lastStrike = max($strikes);
        $tailProfit = $profitAt($lastStrike);
        if (abs($tailSlope) > 0.000001) {
            $tailBreakEven = $lastStrike - ($tailProfit / $tailSlope);
            if ($tailBreakEven > $lastStrike + 0.000001) $breakEvens[] = $tailBreakEven;
        }
        sort($breakEvens, SORT_NUMERIC);
        $uniqueBreakEvens = [];
        foreach ($breakEvens as $breakEven) {
            if ($uniqueBreakEvens === [] || abs($breakEven - $uniqueBreakEvens[count($uniqueBreakEvens) - 1]) > 0.000001) {
                $uniqueBreakEvens[] = round($breakEven, 6);
            }
        }
        $boundaryProfits = array_map($profitAt, $boundaries);
        $scenarioPrices = array_values(array_unique(array_merge($boundaries, array_map(static fn(float $strike): float => $strike * 1.1, $strikes), $uniqueBreakEvens)));
        $scenarios = [];
        foreach ($scenarioPrices as $price) {
            $scenarios[] = ['underlying_price' => $price, 'net_profit' => round($profitAt($price), 6)];
        }

        return [
            'supported' => true,
            'unsupported_reason' => null,
            'expiration_date' => $expirations[0],
            'recognition' => $recognition,
            'initial_cash_flow' => round($cashFlow, 6),
            'total_costs' => round($costs, 6),
            'net_initial_cash_flow' => round($netInitial, 6),
            'break_even_points' => $uniqueBreakEvens,
            'max_profit' => $tailSlope > 0 ? null : round(max(0.0, ...$boundaryProfits), 6),
            'max_loss' => $tailSlope < 0 ? null : round(max(0.0, -min($boundaryProfits)), 6),
            'scenarios' => $scenarios,
        ];
    }

    public static function recognize(array $legs): array
    {
        $unrecognized = ['status' => 'UNRECOGNIZED', 'strategy' => null, 'reason' => 'Combinação não reconhecida pelas regras explícitas disponíveis.'];
        if (count($legs) === 1) {
            return ['status' => 'RECOGNIZED', 'strategy' => ($legs[0]['side'] === 'BUY' ? 'LONG_' : 'SHORT_') . $legs[0]['option_type'], 'reason' => null];
        }

        $notional = static fn(array $leg): int => (int) $leg['quantity'] * (int) $leg['contract_multiplier'];
        $equalNotional = count(array_unique(array_map($notional, $legs))) === 1;
        if (count($legs) === 2 && $equalNotional) {
            $call = null;
            $put = null;
            foreach ($legs as $leg) {
                if ($leg['option_type'] === 'CALL') $call = $leg;
                if ($leg['option_type'] === 'PUT') $put = $leg;
            }
            if ($call !== null && $put !== null && $call['side'] === $put['side']) {
                if ((float) $call['strike_price'] === (float) $put['strike_price']) {
                    return ['status' => 'RECOGNIZED', 'strategy' => $call['side'] === 'BUY' ? 'LONG_STRADDLE' : 'SHORT_STRADDLE', 'reason' => null];
                }
                if ((float) $put['strike_price'] < (float) $call['strike_price']) {
                    return ['status' => 'RECOGNIZED', 'strategy' => $call['side'] === 'BUY' ? 'LONG_STRANGLE' : 'SHORT_STRANGLE', 'reason' => null];
                }
            }
            if ($legs[0]['option_type'] === $legs[1]['option_type'] && $legs[0]['side'] !== $legs[1]['side']) {
                $ordered = $legs;
                usort($ordered, static fn(array $a, array $b): int => (float) $a['strike_price'] <=> (float) $b['strike_price']);
                if ($ordered[0]['option_type'] === 'CALL') {
                    return ['status' => 'RECOGNIZED', 'strategy' => $ordered[0]['side'] === 'BUY' ? 'BULL_CALL_SPREAD' : 'BEAR_CALL_SPREAD', 'reason' => null];
                }
                return ['status' => 'RECOGNIZED', 'strategy' => $ordered[0]['side'] === 'BUY' ? 'BULL_PUT_SPREAD' : 'BEAR_PUT_SPREAD', 'reason' => null];
            }
        }

        if (count($legs) === 3) {
            $ordered = $legs;
            usort($ordered, static fn(array $a, array $b): int => (float) $a['strike_price'] <=> (float) $b['strike_price']);
            $sameType = count(array_unique(array_column($ordered, 'option_type'))) === 1;
            $wing = (float) $ordered[1]['strike_price'] - (float) $ordered[0]['strike_price'];
            $otherWing = (float) $ordered[2]['strike_price'] - (float) $ordered[1]['strike_price'];
            if ($sameType && abs($wing - $otherWing) < 0.000001
                && $notional($ordered[0]) === $notional($ordered[2])
                && $notional($ordered[1]) === 2 * $notional($ordered[0])
                && $ordered[0]['side'] === $ordered[2]['side']
                && $ordered[1]['side'] !== $ordered[0]['side']) {
                $direction = $ordered[0]['side'] === 'BUY' ? 'LONG_' : 'SHORT_';
                return ['status' => 'RECOGNIZED', 'strategy' => $direction . $ordered[0]['option_type'] . '_BUTTERFLY', 'reason' => null];
            }
        }

        if (count($legs) === 4 && $equalNotional) {
            $puts = array_values(array_filter($legs, static fn(array $leg): bool => $leg['option_type'] === 'PUT'));
            $calls = array_values(array_filter($legs, static fn(array $leg): bool => $leg['option_type'] === 'CALL'));
            usort($puts, static fn(array $a, array $b): int => (float) $a['strike_price'] <=> (float) $b['strike_price']);
            usort($calls, static fn(array $a, array $b): int => (float) $a['strike_price'] <=> (float) $b['strike_price']);
            if (count($puts) === 2 && count($calls) === 2
                && (float) $puts[0]['strike_price'] < (float) $puts[1]['strike_price']
                && (float) $puts[1]['strike_price'] < (float) $calls[0]['strike_price']
                && (float) $calls[0]['strike_price'] < (float) $calls[1]['strike_price']
                && [$puts[0]['side'], $puts[1]['side'], $calls[0]['side'], $calls[1]['side']] === ['BUY', 'SELL', 'SELL', 'BUY']) {
                return ['status' => 'RECOGNIZED', 'strategy' => 'IRON_CONDOR', 'reason' => null];
            }
        }
        return $unrecognized;
    }
}
