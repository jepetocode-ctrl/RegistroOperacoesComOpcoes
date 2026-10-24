<?php

declare(strict_types=1);

namespace App\Domain;

final class StrategyCatalog
{
    private static function slot(string $key, string $label, string $side, string $type, int $ratio = 1, string $instrument = 'OPTION'): array
    {
        return [
            'slot_key' => $key,
            'label' => $label,
            'instrument_type' => $instrument,
            'side' => $side,
            'option_type' => $type,
            'ratio' => $ratio,
        ];
    }

    private static function template(string $code, string $name, array $slots, array $constraints = [], string $support = 'REGISTRATION'): array
    {
        return [
            'code' => $code,
            'name' => $name,
            'description' => $name,
            'category' => 'OPTIONS',
            'support_level' => $support,
            'slots' => $slots,
            'constraints' => $constraints,
        ];
    }

    private static function strikeOrder(array $keys): array
    {
        return ['type' => 'strike_order', 'slots' => $keys];
    }

    private static function sameExpiration(array $keys): array
    {
        return ['type' => 'same_expiration', 'slots' => $keys];
    }

    private static function sameStrike(array $keys): array
    {
        return ['type' => 'same_strike', 'slots' => $keys];
    }

    public static function all(): array
    {
        $longCall = self::slot('long_call', 'Compra de Call', 'BUY', 'CALL');
        $shortCall = self::slot('short_call', 'Venda de Call', 'SELL', 'CALL');
        $longPut = self::slot('long_put', 'Compra de Put', 'BUY', 'PUT');
        $shortPut = self::slot('short_put', 'Venda de Put', 'SELL', 'PUT');
        $catalog = [
            self::template('long_call', 'Compra de Call', [$longCall], [], 'PAYOFF'),
            self::template('short_call', 'Venda de Call', [$shortCall], [], 'PAYOFF'),
            self::template('long_put', 'Compra de Put', [$longPut], [], 'PAYOFF'),
            self::template('short_put', 'Venda de Put', [$shortPut], [], 'PAYOFF'),
            self::template('call_spread_buy', 'Compra de Call Spread', [
                self::slot('long_call', 'Compra de Call — strike inferior', 'BUY', 'CALL'),
                self::slot('short_call', 'Venda de Call — strike superior', 'SELL', 'CALL'),
            ], [self::strikeOrder(['long_call', 'short_call']), self::sameExpiration(['long_call', 'short_call'])], 'PAYOFF'),
            self::template('call_spread_sell', 'Venda de Call Spread', [
                self::slot('short_call', 'Venda de Call — strike inferior', 'SELL', 'CALL'),
                self::slot('long_call', 'Compra de Call — strike superior', 'BUY', 'CALL'),
            ], [self::strikeOrder(['short_call', 'long_call']), self::sameExpiration(['short_call', 'long_call'])], 'PAYOFF'),
            self::template('put_spread_buy', 'Compra de Put Spread', [
                self::slot('short_put', 'Venda de Put — strike inferior', 'SELL', 'PUT'),
                self::slot('long_put', 'Compra de Put — strike superior', 'BUY', 'PUT'),
            ], [self::strikeOrder(['short_put', 'long_put']), self::sameExpiration(['short_put', 'long_put'])], 'PAYOFF'),
            self::template('put_spread_sell', 'Venda de Put Spread', [
                self::slot('long_put', 'Compra de Put — strike inferior', 'BUY', 'PUT'),
                self::slot('short_put', 'Venda de Put — strike superior', 'SELL', 'PUT'),
            ], [self::strikeOrder(['long_put', 'short_put']), self::sameExpiration(['long_put', 'short_put'])], 'PAYOFF'),
            self::template('long_straddle', 'Compra de Straddle', [$longCall, $longPut], [
                self::sameStrike(['long_call', 'long_put']), self::sameExpiration(['long_call', 'long_put']),
            ], 'PAYOFF'),
            self::template('short_straddle', 'Venda de Straddle', [$shortCall, $shortPut], [
                self::sameStrike(['short_call', 'short_put']), self::sameExpiration(['short_call', 'short_put']),
            ], 'PAYOFF'),
            self::template('long_strangle', 'Compra de Strangle', [
                self::slot('long_put', 'Compra de Put — strike inferior', 'BUY', 'PUT'),
                self::slot('long_call', 'Compra de Call — strike superior', 'BUY', 'CALL'),
            ], [self::strikeOrder(['long_put', 'long_call']), self::sameExpiration(['long_put', 'long_call'])], 'PAYOFF'),
            self::template('short_strangle', 'Venda de Strangle', [
                self::slot('short_put', 'Venda de Put — strike inferior', 'SELL', 'PUT'),
                self::slot('short_call', 'Venda de Call — strike superior', 'SELL', 'CALL'),
            ], [self::strikeOrder(['short_put', 'short_call']), self::sameExpiration(['short_put', 'short_call'])], 'PAYOFF'),
            self::template('long_call_fly', 'Fly de Call comprada', [
                self::slot('wing_low', 'Compra de Call — strike inferior', 'BUY', 'CALL'),
                self::slot('body', 'Venda de 2 Calls — strike central', 'SELL', 'CALL', 2),
                self::slot('wing_high', 'Compra de Call — strike superior', 'BUY', 'CALL'),
            ], [self::strikeOrder(['wing_low', 'body', 'wing_high']), self::sameExpiration(['wing_low', 'body', 'wing_high'])], 'PAYOFF'),
            self::template('long_put_fly', 'Fly de Put comprada', [
                self::slot('wing_low', 'Compra de Put — strike inferior', 'BUY', 'PUT'),
                self::slot('body', 'Venda de 2 Puts — strike central', 'SELL', 'PUT', 2),
                self::slot('wing_high', 'Compra de Put — strike superior', 'BUY', 'PUT'),
            ], [self::strikeOrder(['wing_low', 'body', 'wing_high']), self::sameExpiration(['wing_low', 'body', 'wing_high'])], 'PAYOFF'),
            self::template('iron_condor', 'Iron Condor comprado', [
                self::slot('long_put', 'Compra de Put — asa inferior', 'BUY', 'PUT'),
                self::slot('short_put', 'Venda de Put — corpo inferior', 'SELL', 'PUT'),
                self::slot('short_call', 'Venda de Call — corpo superior', 'SELL', 'CALL'),
                self::slot('long_call', 'Compra de Call — asa superior', 'BUY', 'CALL'),
            ], [
                self::strikeOrder(['long_put', 'short_put', 'short_call', 'long_call']),
                self::sameExpiration(['long_put', 'short_put', 'short_call', 'long_call']),
            ], 'PAYOFF'),
            self::template('long_call_condor', 'Condor de Call comprado', [
                self::slot('wing_low', 'Compra de Call — strike 1', 'BUY', 'CALL'),
                self::slot('body_low', 'Venda de Call — strike 2', 'SELL', 'CALL'),
                self::slot('body_high', 'Venda de Call — strike 3', 'SELL', 'CALL'),
                self::slot('wing_high', 'Compra de Call — strike 4', 'BUY', 'CALL'),
            ], [
                self::strikeOrder(['wing_low', 'body_low', 'body_high', 'wing_high']),
                self::sameExpiration(['wing_low', 'body_low', 'body_high', 'wing_high']),
            ]),
            self::template('risk_reversal_bullish', 'Risk Reversal de alta', [
                self::slot('short_put', 'Venda de Put — strike inferior', 'SELL', 'PUT'),
                self::slot('long_call', 'Compra de Call — strike superior', 'BUY', 'CALL'),
            ], [self::strikeOrder(['short_put', 'long_call']), self::sameExpiration(['short_put', 'long_call'])]),
            self::template('seagull_bullish', 'Seagull de alta', [
                self::slot('short_put', 'Venda de Put — strike inferior', 'SELL', 'PUT'),
                self::slot('long_call', 'Compra de Call — strike central', 'BUY', 'CALL'),
                self::slot('short_call', 'Venda de Call — strike superior', 'SELL', 'CALL'),
            ], [self::strikeOrder(['short_put', 'long_call', 'short_call']), self::sameExpiration(['short_put', 'long_call', 'short_call'])]),
            self::template('collar_options_only', 'Collar sem ativo-objeto (opções)', [
                self::slot('long_put', 'Compra de Put', 'BUY', 'PUT'),
                self::slot('short_call', 'Venda de Call', 'SELL', 'CALL'),
            ], [self::sameExpiration(['long_put', 'short_call'])]),
            self::template('fence_options_only', 'Fence sem ativo-objeto (opções)', [
                self::slot('long_put', 'Compra de Put — strike inferior', 'BUY', 'PUT'),
                self::slot('short_put', 'Venda de Put — strike central', 'SELL', 'PUT'),
                self::slot('short_call', 'Venda de Call', 'SELL', 'CALL'),
            ], [
                self::strikeOrder(['long_put', 'short_put', 'short_call']),
                self::sameExpiration(['long_put', 'short_put', 'short_call']),
            ]),
            self::template('collar', 'Collar (requer ativo-objeto)', [
                self::slot('asset', 'Ativo-objeto', 'BUY', '', 1, 'ACTION'),
                self::slot('long_put', 'Compra de Put protetora', 'BUY', 'PUT'),
                self::slot('short_call', 'Venda de Call coberta', 'SELL', 'CALL'),
            ], [], 'UNSUPPORTED'),
            self::template('fence', 'Fence (requer ativo-objeto)', [
                self::slot('asset', 'Ativo-objeto', 'BUY', '', 1, 'ACTION'),
                self::slot('long_put', 'Compra de Put protetora', 'BUY', 'PUT'),
                self::slot('short_call', 'Venda de Call', 'SELL', 'CALL'),
                self::slot('short_put', 'Venda de Put', 'SELL', 'PUT'),
            ], [], 'UNSUPPORTED'),
        ];

        return $catalog;
    }
}
