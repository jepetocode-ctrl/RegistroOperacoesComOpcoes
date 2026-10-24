<?php

declare(strict_types=1);

namespace App\Domain;

final class LegacyStrategyConverter
{
    public static function convert(?string $raw): array
    {
        $fail = static fn(string $reason): array => [
            'slots' => [],
            'constraints' => [],
            'needs_review' => true,
            'reason' => $reason,
        ];
        if ($raw === null || trim($raw) === '') return $fail('Estratégia antiga sem pernas legíveis.');
        try {
            $legs = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            return $fail('JSON legado inválido; conteúdo original preservado para revisão.');
        }
        if (!is_array($legs) || !array_is_list($legs) || $legs === []) return $fail('Formato legado não reconhecido.');

        $slots = [];
        foreach ($legs as $index => $leg) {
            if (!is_array($leg)) return $fail('Uma ou mais pernas não possuem formato de objeto.');
            $side = strtoupper((string) ($leg['side'] ?? ''));
            $type = strtoupper((string) ($leg['option_type'] ?? ''));
            $ratio = filter_var($leg['ratio'] ?? $leg['quantity'] ?? 1, FILTER_VALIDATE_INT);
            if (!in_array($side, ['BUY', 'SELL'], true) || !in_array($type, ['CALL', 'PUT'], true) || $ratio === false || $ratio < 1) {
                return $fail('Uma ou mais pernas não têm lado, tipo de opção ou proporção interpretável.');
            }
            $slots[] = [
                'slot_key' => 'legacy_leg_' . ($index + 1),
                'label' => 'Perna ' . ($index + 1) . ' — ' . ($side === 'BUY' ? 'Compra' : 'Venda') . ' de ' . ($type === 'CALL' ? 'Call' : 'Put'),
                'instrument_type' => 'OPTION',
                'side' => $side,
                'option_type' => $type,
                'ratio' => $ratio,
            ];
        }
        return ['slots' => $slots, 'constraints' => [], 'needs_review' => false, 'reason' => null];
    }
}
