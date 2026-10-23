<?php

declare(strict_types=1);

namespace App\Validators;

final class FinancialRegistrationValidator
{
    public static function validate(string $resource, array $data, bool $partial = false): array
    {
        $errors = [];
        $required = match ($resource) {
            'assets' => ['symbol', 'name'],
            'option_contracts' => ['asset_id', 'code', 'option_type', 'strike_price', 'expiration_date'],
            'strategies' => ['name'],
            default => ['resource'],
        };
        if (!$partial) {
            foreach ($required as $field) {
                if (!isset($data[$field]) || trim((string) $data[$field]) === '') $errors[$field] = 'Campo obrigatório.';
            }
        }
        $limits = ['symbol' => 30, 'name' => 120, 'code' => 60, 'asset_type' => 30, 'description' => 5000];
        foreach ($limits as $field => $max) {
            if (isset($data[$field]) && (!is_string($data[$field]) || mb_strlen($data[$field]) > $max)) $errors[$field] = "Valor inválido (máximo {$max} caracteres).";
        }
        if (isset($data['symbol']) && !preg_match('/^[A-Za-z0-9._-]+$/', (string) $data['symbol'])) $errors['symbol'] = 'Símbolo inválido.';
        if (isset($data['option_type']) && !in_array(strtoupper((string) $data['option_type']), ['CALL', 'PUT'], true)) $errors['option_type'] = 'Tipo deve ser CALL ou PUT.';
        if (isset($data['strike_price']) && (!is_numeric($data['strike_price']) || (float) $data['strike_price'] <= 0)) $errors['strike_price'] = 'Preço de exercício deve ser positivo.';
        if (isset($data['asset_id']) && filter_var($data['asset_id'], FILTER_VALIDATE_INT) === false) $errors['asset_id'] = 'Ativo inválido.';
        if (isset($data['contract_multiplier']) && (!filter_var($data['contract_multiplier'], FILTER_VALIDATE_INT) || (int) $data['contract_multiplier'] < 1)) $errors['contract_multiplier'] = 'Multiplicador deve ser inteiro positivo.';
        if (isset($data['expiration_date'])) {
            $date = \DateTimeImmutable::createFromFormat('!Y-m-d', (string) $data['expiration_date']);
            if (!$date || $date->format('Y-m-d') !== $data['expiration_date']) $errors['expiration_date'] = 'Data deve estar no formato AAAA-MM-DD.';
        }
        if (isset($data['status']) && !in_array($data['status'], ['ACTIVE', 'INACTIVE'], true)) $errors['status'] = 'Status inválido.';
        if (isset($data['legs']) && !is_array($data['legs'])) $errors['legs'] = 'As pernas da estratégia devem ser uma lista.';
        return ['valid' => $errors === [], 'errors' => $errors];
    }
}
