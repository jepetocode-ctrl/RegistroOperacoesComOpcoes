<?php

declare(strict_types=1);

namespace App\Domain;

final class TemplateConstraints
{
    public const TYPES = ['strike_order', 'same_strike', 'same_expiration', 'different_expiration', 'same_asset'];

    public static function validateDefinition(mixed $constraints, array $slotKeys): array
    {
        if (!is_array($constraints) || ($constraints !== [] && !array_is_list($constraints))) {
            return ['constraints' => 'Restrições devem ser informadas como lista.'];
        }
        $errors = [];
        foreach ($constraints as $index => $constraint) {
            $field = "constraints.{$index}";
            if (!is_array($constraint) || !in_array($constraint['type'] ?? null, self::TYPES, true)) {
                $errors[$field] = 'Tipo de restrição desconhecido.';
                continue;
            }
            $slots = $constraint['slots'] ?? null;
            if (!is_array($slots) || !array_is_list($slots) || count($slots) < 2 || count($slots) !== count(array_unique($slots))) {
                $errors[$field] = 'A restrição exige ao menos dois slots distintos.';
                continue;
            }
            foreach ($slots as $slot) {
                if (!is_string($slot) || !in_array($slot, $slotKeys, true)) {
                    $errors[$field] = 'A restrição referencia um slot inexistente.';
                    break;
                }
            }
        }
        return $errors;
    }

    public static function evaluate(array $constraints, array $contractsBySlot): array
    {
        $errors = [];
        foreach ($constraints as $index => $constraint) {
            $slots = $constraint['slots'];
            $field = "constraints.{$index}";
            switch ($constraint['type']) {
                case 'strike_order':
                    for ($i = 0; $i < count($slots) - 1; $i++) {
                        if ((float) $contractsBySlot[$slots[$i]]['strike_price'] >= (float) $contractsBySlot[$slots[$i + 1]]['strike_price']) {
                            $errors[$field] = "O strike de {$slots[$i]} deve ser menor que o de {$slots[$i + 1]}.";
                            break;
                        }
                    }
                    break;
                case 'same_strike':
                    $first = (float) $contractsBySlot[$slots[0]]['strike_price'];
                    foreach ($slots as $slot) {
                        if (abs((float) $contractsBySlot[$slot]['strike_price'] - $first) > 0.000001) {
                            $errors[$field] = 'Os slots devem ter o mesmo strike.';
                            break;
                        }
                    }
                    break;
                case 'same_expiration':
                case 'different_expiration':
                    $dates = array_map(static fn(string $slot): string => (string) $contractsBySlot[$slot]['expiration_date'], $slots);
                    $same = count(array_unique($dates)) === 1;
                    if (($constraint['type'] === 'same_expiration' && !$same) || ($constraint['type'] === 'different_expiration' && $same)) {
                        $errors[$field] = $constraint['type'] === 'same_expiration'
                            ? 'Os slots devem ter o mesmo vencimento.'
                            : 'Os slots devem ter vencimentos diferentes.';
                    }
                    break;
                case 'same_asset':
                    $assets = array_map(static fn(string $slot): int => (int) $contractsBySlot[$slot]['asset_id'], $slots);
                    if (count(array_unique($assets)) !== 1) $errors[$field] = 'Os slots devem ter o mesmo ativo-objeto.';
                    break;
            }
        }
        return $errors;
    }
}
