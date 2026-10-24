<?php

declare(strict_types=1);

namespace App\Services;

use App\Domain\PositionPayoff;
use App\Domain\TemplateConstraints;
use App\Repositories\StrategyTemplateRepository;
use App\Repositories\StructuredPositionRepository;
use DateTimeImmutable;
use PDO;
use Throwable;

final class StructuredPositionService
{
    public function __construct(
        private readonly PDO $pdo,
        private readonly StrategyTemplateRepository $templates,
        private readonly StructuredPositionRepository $positions,
    ) {}

    public function register(int $userId, array $input): array
    {
        $errors = [];
        $templateId = filter_var($input['strategy_template_id'] ?? null, FILTER_VALIDATE_INT);
        $template = $templateId === false || $templateId === null || $templateId < 1
            ? null
            : $this->templates->findVisible($userId, $templateId);
        if ($template === null) return $this->fail(['strategy_template_id' => 'Modelo de estratégia inexistente.'], 404);
        if ($template['needs_review']) return $this->fail(['strategy_template_id' => 'Este modelo legado exige revisão antes de ser usado.']);
        if ($template['support_level'] === 'UNSUPPORTED' || $template['slots'] === []) {
            return $this->fail(['strategy_template_id' => 'Este modelo ainda não pode ser registrado com o ledger disponível.']);
        }
        foreach ($template['slots'] as $slot) {
            if ($slot['instrument_type'] !== 'OPTION') {
                return $this->fail(['strategy_template_id' => 'O modelo contém ativo-objeto; o ledger de ações ainda não está disponível para esta estrutura.']);
            }
        }

        $base = filter_var($input['base_quantity'] ?? null, FILTER_VALIDATE_INT);
        if ($base === false || $base === null || $base < 1 || $base > 100000) $errors['base_quantity'] = 'Informe uma quantidade-base inteira entre 1 e 100000.';
        $executedAt = trim((string) ($input['executed_at'] ?? ''));
        if ($executedAt === '') $executedAt = date('Y-m-d H:i:s');
        $executedAt = $this->normalizeDateTime($executedAt);
        if (!$this->validDateTime($executedAt)) $errors['executed_at'] = 'Data/hora inexistente ou inválida.';

        $assetId = filter_var($input['asset_id'] ?? null, FILTER_VALIDATE_INT);
        if ($assetId === false || $assetId === null || $assetId < 1) $errors['asset_id'] = 'Selecione o ativo-objeto.';
        else {
            $assetQuery = $this->pdo->prepare("SELECT id FROM assets WHERE id = :id AND user_id = :user_id AND status = 'ACTIVE'");
            $assetQuery->execute(['id' => $assetId, 'user_id' => $userId]);
            if (!$assetQuery->fetchColumn()) $errors['asset_id'] = 'Ativo inexistente, inativo ou de outro usuário.';
        }

        $currency = strtoupper(trim((string) ($input['currency'] ?? 'BRL')));
        if (!preg_match('/^[A-Z]{3}$/', $currency)) $errors['currency'] = 'Informe uma moeda ISO de três letras.';
        $broker = trim((string) ($input['broker'] ?? ''));
        if (mb_strlen($broker) > 120) $errors['broker'] = 'Corretora deve ter até 120 caracteres.';
        $generalCosts = $this->nonNegativeNumber($input['general_costs'] ?? 0, 'general_costs', $errors);

        $legsInput = $input['legs'] ?? null;
        $legsByKey = [];
        if (!is_array($legsInput) || !array_is_list($legsInput)) {
            $errors['legs'] = 'Informe as pernas da posição.';
        } else {
            foreach ($legsInput as $index => $leg) {
                $key = is_array($leg) ? (string) ($leg['slot_key'] ?? '') : '';
                if ($key === '' || isset($legsByKey[$key])) {
                    $errors["legs.{$index}.slot_key"] = 'Slot ausente ou repetido.';
                    continue;
                }
                $legsByKey[$key] = $leg;
            }
        }
        $slotKeys = array_column($template['slots'], 'slot_key');
        if (!isset($errors['legs'])) {
            foreach (array_diff($slotKeys, array_keys($legsByKey)) as $missing) $errors["legs.{$missing}"] = 'Perna obrigatória não informada.';
            foreach (array_diff(array_keys($legsByKey), $slotKeys) as $extra) $errors["legs.{$extra}"] = 'Slot não pertence ao modelo.';
        }
        if ($errors !== []) return $this->fail($errors);

        $contractsBySlot = [];
        foreach ($template['slots'] as $slot) {
            $key = $slot['slot_key'];
            $legInput = $legsByKey[$key];
            $contractId = filter_var($legInput['option_contract_id'] ?? null, FILTER_VALIDATE_INT);
            if ($contractId === false || $contractId === null || $contractId < 1) {
                $errors["legs.{$key}.option_contract_id"] = 'Selecione um contrato válido.';
                continue;
            }
            $contractQuery = $this->pdo->prepare("SELECT id, asset_id, code, option_type, strike_price, expiration_date, contract_multiplier FROM option_contracts WHERE id = :id AND user_id = :user_id AND status = 'ACTIVE'");
            $contractQuery->execute(['id' => $contractId, 'user_id' => $userId]);
            $contract = $contractQuery->fetch(PDO::FETCH_ASSOC);
            if (!$contract) {
                $errors["legs.{$key}.option_contract_id"] = 'Contrato inexistente, inativo ou de outro usuário.';
                continue;
            }
            if ((int) $contract['asset_id'] !== (int) $assetId) $errors["legs.{$key}.option_contract_id"] = 'O contrato deve pertencer ao ativo-objeto selecionado.';
            if (strtoupper((string) $contract['option_type']) !== $slot['option_type']) $errors["legs.{$key}.option_contract_id"] = 'O contrato não corresponde ao tipo de opção definido no slot.';
            $price = $this->nonNegativeNumber($legInput['unit_price'] ?? null, "legs.{$key}.unit_price", $errors);
            $legCosts = $this->nonNegativeNumber($legInput['costs'] ?? 0, "legs.{$key}.costs", $errors);
            $contractsBySlot[$key] = $contract + ['unit_price' => $price, 'specific_costs' => $legCosts];
        }
        if ($errors === [] && count($contractsBySlot) === count($template['slots'])) {
            $errors += TemplateConstraints::evaluate($template['constraints'], $contractsBySlot);
        }
        if ($errors !== []) return $this->fail($errors);

        $classification = PositionPayoff::analyze($this->payoffLegs($template['slots'], $contractsBySlot, (int) $base))['recognition'];
        $expected = $this->expectedRecognition($template['code']);
        if (($classification['status'] !== 'RECOGNIZED' || ($expected !== null && $expected !== $classification['strategy']))
            && !filter_var($input['confirm_classification'] ?? false, FILTER_VALIDATE_BOOLEAN)) {
            return [
                'success' => false,
                'status' => 409,
                'message' => 'A classificação declarada diverge da inferida; revise e confirme explicitamente para continuar.',
                'errors' => ['classification' => 'Declarada: ' . $template['name'] . '. Inferida: ' . ($classification['strategy'] ?? $classification['status']) . '.'],
                'data' => ['declared' => $template['name'], 'inferred' => $classification],
            ];
        }
        $uuid = $this->uuid();
        $name = trim((string) ($input['name'] ?? '')) ?: $template['name'];
        if (mb_strlen($name) > 120) $name = mb_substr($name, 0, 120);
        $sharedCosts = (float) $generalCosts;
        $slotsCount = count($template['slots']);
        $allocatedShared = [];
        $costWeights = [];
        $totalCostWeight = 0;
        foreach ($template['slots'] as $slot) {
            $key = $slot['slot_key'];
            $costWeights[$key] = (int) $slot['ratio'] * (int) $base * (int) $contractsBySlot[$key]['contract_multiplier'];
            $totalCostWeight += $costWeights[$key];
        }
        $remainingShared = round($sharedCosts, 6);
        foreach ($template['slots'] as $index => $slot) {
            $share = $index === $slotsCount - 1
                ? $remainingShared
                : round($sharedCosts * ($costWeights[$slot['slot_key']] / $totalCostWeight), 6);
            $allocatedShared[$slot['slot_key']] = $share;
            $remainingShared = round($remainingShared - $share, 6);
        }

        try {
            $this->pdo->beginTransaction();
            $positionId = $this->positions->insertPosition([
                'uuid' => $uuid,
                'user_id' => $userId,
                'template_id' => $template['id'],
                'asset_id' => (int) $assetId,
                'name' => $name,
                'base_quantity' => (int) $base,
                'executed_at' => $executedAt,
                'currency' => $currency,
                'broker' => $broker === '' ? null : $broker,
                'general_costs' => round($sharedCosts, 6),
                'declared_strategy' => $template['name'],
                'inferred_strategy' => $classification['strategy'],
                'classification_status' => $classification['status'],
                'notes' => trim((string) ($input['notes'] ?? '')) ?: null,
            ]);
            foreach ($template['slots'] as $slot) {
                $key = $slot['slot_key'];
                $contract = $contractsBySlot[$key];
                $quantity = (int) $slot['ratio'] * (int) $base;
                $price = round((float) $contract['unit_price'], 6);
                $costs = round((float) $contract['specific_costs'] + $allocatedShared[$key], 6);
                $gross = round($price * $quantity * (int) $contract['contract_multiplier'], 6) * ($slot['side'] === 'BUY' ? -1 : 1);
                $operationId = $this->positions->insertOperation([
                    'user_id' => $userId,
                    'contract_id' => (int) $contract['id'],
                    'strategy_id' => $template['legacy_strategy_id'] === null ? null : (int) $template['legacy_strategy_id'],
                    'structured_id' => $positionId,
                    'side' => $slot['side'],
                    'quantity' => $quantity,
                    'unit_price' => $price,
                    'gross' => $gross,
                    'costs' => $costs,
                    'executed_at' => $executedAt,
                    'operation_kind' => 'OPEN',
                    'notes' => 'Posição estruturada ' . $uuid . ' — ' . $slot['label'],
                ]);
                $this->positions->insertLeg($positionId, $key, $operationId, (int) $contract['id']);
                $after = [
                    'structured_position_uuid' => $uuid,
                    'slot_key' => $key,
                    'side' => $slot['side'],
                    'quantity' => $quantity,
                    'unit_price' => $price,
                    'costs' => $costs,
                    'gross_cash_flow' => $gross,
                ];
                $this->insertHistory($userId, $operationId, 'STRUCTURED_POSITION_OPENED', $after);
            }
            $this->recordAudit($userId, 'STRUCTURED_POSITION_OPENED', $positionId);
            $this->pdo->commit();
        } catch (Throwable $exception) {
            if ($this->pdo->inTransaction()) $this->pdo->rollBack();
            throw $exception;
        }

        return ['success' => true, 'status' => 201, 'data' => $this->find($userId, $uuid)];
    }

    public function update(int $userId, string $uuid, array $input): array
    {
        $position = $this->find($userId, $uuid);
        if ($position === null) return ['success' => false, 'status' => 404, 'message' => 'Posição estruturada não encontrada.'];
        if (array_filter($position['legs'], static fn(array $leg): bool => $leg['entry_kind'] !== 'INITIAL' || (int) $leg['closed_quantity'] > 0) !== []
            || $position['legs'] === [] || count(array_filter($position['legs'], static fn(array $leg): bool => (int) $leg['open_quantity'] > 0)) !== count($position['legs'])) {
            return $this->fail(['position' => 'Só é possível editar posições abertas sem encerramentos ou rolagens. Registre uma rolagem ou encerramento para preservar o histórico.'], 409);
        }

        $templateId = filter_var($input['strategy_template_id'] ?? null, FILTER_VALIDATE_INT);
        $template = $templateId === false || $templateId === null || $templateId < 1
            ? null
            : $this->templates->findVisible($userId, $templateId);
        if ($template === null) return $this->fail(['strategy_template_id' => 'Modelo de estratégia inexistente.'], 404);
        if ($template['needs_review'] || $template['support_level'] === 'UNSUPPORTED'
            || $template['slots'] === []
            || count($template['slots']) !== count($position['legs'])
            || count(array_filter($template['slots'], static fn(array $slot): bool => $slot['instrument_type'] !== 'OPTION')) > 0) {
            return $this->fail(['strategy_template_id' => 'O modelo precisa conter opções e o mesmo número de pernas da posição.']);
        }

        $errors = [];
        $base = filter_var($input['base_quantity'] ?? null, FILTER_VALIDATE_INT);
        if ($base === false || $base === null || $base < 1 || $base > 100000) $errors['base_quantity'] = 'Informe uma quantidade-base inteira entre 1 e 100000.';
        $executedAt = $this->normalizeDateTime(trim((string) ($input['executed_at'] ?? '')));
        if (!$this->validDateTime($executedAt)) $errors['executed_at'] = 'Data/hora inexistente ou inválida.';
        $name = trim((string) ($input['name'] ?? '')) ?: $template['name'];
        if (mb_strlen($name) > 120) $errors['name'] = 'O nome deve ter até 120 caracteres.';
        $generalCosts = $this->nonNegativeNumber($input['general_costs'] ?? 0, 'general_costs', $errors);
        $legsInput = $input['legs'] ?? null;
        $legsByKey = [];
        if (!is_array($legsInput) || !array_is_list($legsInput)) {
            $errors['legs'] = 'Informe todas as pernas da posição.';
        } else {
            foreach ($legsInput as $index => $leg) {
                $key = is_array($leg) ? (string) ($leg['slot_key'] ?? '') : '';
                if ($key === '' || isset($legsByKey[$key])) {
                    $errors["legs.{$index}.slot_key"] = 'Slot ausente ou repetido.';
                    continue;
                }
                $legsByKey[$key] = $leg;
            }
        }
        $slotKeys = array_column($template['slots'], 'slot_key');
        if (!isset($errors['legs'])) {
            foreach (array_diff($slotKeys, array_keys($legsByKey)) as $missing) $errors["legs.{$missing}"] = 'Perna obrigatória não informada.';
            foreach (array_diff(array_keys($legsByKey), $slotKeys) as $extra) $errors["legs.{$extra}"] = 'Slot não pertence ao modelo.';
        }
        if ($errors !== []) return $this->fail($errors);

        $contractsBySlot = [];
        foreach ($template['slots'] as $slot) {
            $key = $slot['slot_key'];
            $entry = $legsByKey[$key];
            $contractId = filter_var($entry['option_contract_id'] ?? null, FILTER_VALIDATE_INT);
            if ($contractId === false || $contractId === null || $contractId < 1) {
                $errors["legs.{$key}.option_contract_id"] = 'Selecione um contrato válido.';
                continue;
            }
            $query = $this->pdo->prepare("SELECT id, asset_id, code, option_type, strike_price, expiration_date, contract_multiplier FROM option_contracts WHERE id = :id AND user_id = :user_id AND status = 'ACTIVE'");
            $query->execute(['id' => $contractId, 'user_id' => $userId]);
            $contract = $query->fetch(PDO::FETCH_ASSOC);
            if (!$contract) {
                $errors["legs.{$key}.option_contract_id"] = 'Contrato inexistente, inativo ou de outro usuário.';
                continue;
            }
            if ((int) $contract['asset_id'] !== (int) $position['asset_id']) $errors["legs.{$key}.option_contract_id"] = 'O contrato deve manter o ativo-objeto da posição.';
            if (strtoupper((string) $contract['option_type']) !== $slot['option_type']) $errors["legs.{$key}.option_contract_id"] = 'O contrato não corresponde ao tipo de opção definido no slot.';
            $contract['unit_price'] = $this->nonNegativeNumber($entry['unit_price'] ?? null, "legs.{$key}.unit_price", $errors);
            $contract['specific_costs'] = $this->nonNegativeNumber($entry['costs'] ?? 0, "legs.{$key}.costs", $errors);
            $contractsBySlot[$key] = $contract;
        }
        if ($errors === [] && count($contractsBySlot) === count($template['slots'])) {
            $errors += TemplateConstraints::evaluate($template['constraints'], $contractsBySlot);
        }
        if ($errors !== []) return $this->fail($errors);

        $payoff = $this->payoffLegs($template['slots'], $contractsBySlot, (int) $base);
        $classification = PositionPayoff::analyze($payoff)['recognition'];
        $expected = $this->expectedRecognition($template['code']);
        if (($classification['status'] !== 'RECOGNIZED' || ($expected !== null && $expected !== $classification['strategy']))
            && !filter_var($input['confirm_classification'] ?? false, FILTER_VALIDATE_BOOLEAN)) {
            return [
                'success' => false,
                'status' => 409,
                'message' => 'A classificação declarada diverge da inferida; revise e confirme explicitamente para continuar.',
                'errors' => ['classification' => 'Declarada: ' . $template['name'] . '. Inferida: ' . ($classification['strategy'] ?? $classification['status']) . '.'],
                'data' => ['declared' => $template['name'], 'inferred' => $classification],
            ];
        }

        $costWeights = [];
        $totalWeight = 0;
        foreach ($template['slots'] as $slot) {
            $key = $slot['slot_key'];
            $costWeights[$key] = (int) $slot['ratio'] * (int) $base * (int) $contractsBySlot[$key]['contract_multiplier'];
            $totalWeight += $costWeights[$key];
        }
        $remainingShared = round($generalCosts, 6);
        $shares = [];
        foreach ($template['slots'] as $index => $slot) {
            $share = $index === count($template['slots']) - 1
                ? $remainingShared
                : round($generalCosts * ($costWeights[$slot['slot_key']] / $totalWeight), 6);
            $shares[$slot['slot_key']] = $share;
            $remainingShared = round($remainingShared - $share, 6);
        }
        $positionBefore = [
            'strategy_template_id' => $position['strategy_template_id'],
            'name' => $position['name'],
            'base_quantity' => $position['base_quantity'],
            'executed_at' => $position['executed_at'],
            'general_costs' => $position['general_costs'],
            'notes' => $position['notes'],
        ];

        try {
            $this->pdo->beginTransaction();
            $this->positions->updatePosition($userId, (int) $position['id'], [
                'template_id' => (int) $template['id'],
                'name' => $name,
                'base_quantity' => (int) $base,
                'executed_at' => $executedAt,
                'general_costs' => round($generalCosts, 6),
                'declared_strategy' => $template['name'],
                'inferred_strategy' => $classification['strategy'],
                'classification_status' => $classification['status'],
                'notes' => trim((string) ($input['notes'] ?? '')) ?: null,
            ]);
            foreach ($template['slots'] as $index => $slot) {
                $key = $slot['slot_key'];
                $contract = $contractsBySlot[$key];
                $operationId = (int) $position['legs'][$index]['operation_id'];
                $quantity = (int) $base * (int) $slot['ratio'];
                $price = round((float) $contract['unit_price'], 6);
                $costs = round((float) $contract['specific_costs'] + $shares[$key], 6);
                $gross = round($price * $quantity * (int) $contract['contract_multiplier'], 6) * ($slot['side'] === 'BUY' ? -1 : 1);
                $before = [
                    'slot_key' => $position['legs'][$index]['slot_key'],
                    'option_contract_id' => (int) $position['legs'][$index]['option_contract_id'],
                    'contract_code' => $position['legs'][$index]['contract_code'],
                    'side' => $position['legs'][$index]['side'],
                    'quantity' => (int) $position['legs'][$index]['quantity'],
                    'unit_price' => (float) $position['legs'][$index]['unit_price'],
                    'costs' => (float) $position['legs'][$index]['costs'],
                    'gross_cash_flow' => (float) $position['legs'][$index]['gross_cash_flow'],
                    'executed_at' => $position['executed_at'],
                ];
                $after = [
                    'structured_position_uuid' => $uuid,
                    'slot_key' => $key,
                    'option_contract_id' => (int) $contract['id'],
                    'contract_code' => $contract['code'],
                    'side' => $slot['side'],
                    'quantity' => $quantity,
                    'unit_price' => $price,
                    'costs' => $costs,
                    'gross_cash_flow' => $gross,
                    'executed_at' => $executedAt,
                ];
                $this->positions->updateOperation($userId, (int) $position['id'], $operationId, [
                    'contract_id' => (int) $contract['id'],
                    'strategy_id' => $template['legacy_strategy_id'] === null ? null : (int) $template['legacy_strategy_id'],
                    'side' => $slot['side'],
                    'quantity' => $quantity,
                    'unit_price' => $price,
                    'gross' => $gross,
                    'costs' => $costs,
                    'executed_at' => $executedAt,
                    'notes' => 'Posição estruturada ' . $uuid . ' — ' . $slot['label'],
                ]);
                $this->positions->updateLeg((int) $position['id'], $operationId, $key, (int) $contract['id']);
                $history = $this->pdo->prepare('INSERT INTO operation_history (user_id,operation_id,action,before_data,after_data) VALUES (:user_id,:operation_id,:action,:before_data,:after_data)');
                $history->execute([
                    'user_id' => $userId,
                    'operation_id' => $operationId,
                    'action' => 'STRUCTURED_POSITION_EDITED',
                    'before_data' => json_encode($before, JSON_THROW_ON_ERROR),
                    'after_data' => json_encode($after, JSON_THROW_ON_ERROR),
                ]);
            }
            $this->recordAudit($userId, 'STRUCTURED_POSITION_EDITED', (int) $position['id'], [
                'before' => $positionBefore,
                'after' => [
                    'strategy_template_id' => (int) $template['id'],
                    'name' => $name,
                    'base_quantity' => (int) $base,
                    'executed_at' => $executedAt,
                    'general_costs' => round($generalCosts, 6),
                    'notes' => trim((string) ($input['notes'] ?? '')) ?: null,
                ],
            ]);
            $this->pdo->commit();
        } catch (Throwable $exception) {
            if ($this->pdo->inTransaction()) $this->pdo->rollBack();
            throw $exception;
        }
        return ['success' => true, 'status' => 200, 'data' => $this->find($userId, $uuid)];
    }

    public function close(int $userId, string $uuid, array $input): array
    {
        $position = $this->positions->find($userId, $uuid);
        if ($position === null) return ['success' => false, 'status' => 404, 'message' => 'Posição estruturada não encontrada.'];
        $lots = filter_var($input['lots'] ?? null, FILTER_VALIDATE_INT);
        if ($lots === false || $lots === null || $lots < 1) return $this->fail(['lots' => 'Informe a quantidade de lotes-base a encerrar.']);
        $prices = $this->normalizeLegPrices($input['legs'] ?? null, $position['legs'], false);
        if ($prices['errors'] !== []) return $this->fail($prices['errors']);

        try {
            $this->pdo->beginTransaction();
            $plan = $this->closePlan($userId, $position, (int) $lots);
            if (isset($plan['errors'])) {
                $this->pdo->rollBack();
                return $this->fail($plan['errors']);
            }
            $this->recordClosures($userId, $position, $plan['allocations'], $prices['legs'], 'STRUCTURED_POSITION_CLOSED');
            $this->recordAudit($userId, 'STRUCTURED_POSITION_CLOSED', (int) $position['id']);
            $this->pdo->commit();
        } catch (Throwable $exception) {
            if ($this->pdo->inTransaction()) $this->pdo->rollBack();
            throw $exception;
        }
        return ['success' => true, 'status' => 200, 'data' => $this->find($userId, $uuid)];
    }

    public function roll(int $userId, string $uuid, array $input): array
    {
        $position = $this->positions->find($userId, $uuid);
        if ($position === null) return ['success' => false, 'status' => 404, 'message' => 'Posição estruturada não encontrada.'];
        $lots = filter_var($input['lots'] ?? null, FILTER_VALIDATE_INT);
        if ($lots === false || $lots === null || $lots < 1) return $this->fail(['lots' => 'Informe a quantidade de lotes-base a rolar.']);
        $template = $this->templates->findVisible($userId, (int) $position['strategy_template_id']);
        if ($template === null || $template['needs_review']) return $this->fail(['strategy_template_id' => 'Modelo da posição indisponível.']);
        $prices = $this->normalizeLegPrices($input['legs'] ?? null, $position['legs'], true);
        if ($prices['errors'] !== []) return $this->fail($prices['errors']);
        $contractsBySlot = [];
        $errors = [];
        foreach ($template['slots'] as $slot) {
            $key = $slot['slot_key'];
            $entry = $prices['legs'][$key];
            $contractId = filter_var($entry['option_contract_id'] ?? null, FILTER_VALIDATE_INT);
            $query = $this->pdo->prepare("SELECT id, asset_id, code, option_type, strike_price, expiration_date, contract_multiplier FROM option_contracts WHERE id = :id AND user_id = :user_id AND status = 'ACTIVE'");
            $query->execute(['id' => $contractId === false ? 0 : $contractId, 'user_id' => $userId]);
            $contract = $query->fetch(PDO::FETCH_ASSOC);
            if (!$contract) {
                $errors["legs.{$key}.option_contract_id"] = 'Contrato de rolagem inexistente, inativo ou de outro usuário.';
                continue;
            }
            if ((int) $contract['asset_id'] !== (int) $position['asset_id']) $errors["legs.{$key}.option_contract_id"] = 'O contrato deve manter o ativo-objeto da posição.';
            if ($contract['option_type'] !== $slot['option_type']) $errors["legs.{$key}.option_contract_id"] = 'O tipo de opção deve corresponder ao slot.';
            $contractsBySlot[$key] = $contract + [
                'unit_price' => (float) $entry['unit_price'],
                'specific_costs' => (float) $entry['costs'],
            ];
        }
        if ($errors === []) $errors += TemplateConstraints::evaluate($template['constraints'], $contractsBySlot);
        if ($errors !== []) return $this->fail($errors);

        try {
            $this->pdo->beginTransaction();
            $plan = $this->closePlan($userId, $position, (int) $lots);
            if (isset($plan['errors'])) {
                $this->pdo->rollBack();
                return $this->fail($plan['errors']);
            }
            $closingRows = $this->recordClosures($userId, $position, $plan['allocations'], $prices['legs'], 'STRUCTURED_POSITION_ROLLED');
            $rolloverIds = [];
            foreach ($template['slots'] as $slot) {
                $key = $slot['slot_key'];
                $contract = $contractsBySlot[$key];
                $quantity = (int) $slot['ratio'] * (int) $lots;
                $price = round((float) $contract['unit_price'], 6);
                $costs = round((float) $contract['specific_costs'], 6);
                $gross = round($price * $quantity * (int) $contract['contract_multiplier'], 6) * ($slot['side'] === 'BUY' ? -1 : 1);
                $openingId = $this->positions->insertOperation([
                    'user_id' => $userId,
                    'contract_id' => (int) $contract['id'],
                    'strategy_id' => null,
                    'structured_id' => (int) $position['id'],
                    'side' => $slot['side'],
                    'quantity' => $quantity,
                    'unit_price' => $price,
                    'gross' => $gross,
                    'costs' => $costs,
                    'executed_at' => date('Y-m-d H:i:s'),
                    'operation_kind' => 'OPEN',
                    'notes' => 'Rolagem da posição estruturada ' . $uuid . ' — ' . $slot['label'],
                ]);
                $this->positions->insertLeg((int) $position['id'], $key, $openingId, (int) $contract['id'], 'ROLLED');
                $openingHistory = [
                    'event' => 'STRUCTURED_POSITION_ROLLED',
                    'structured_position_uuid' => $uuid,
                    'slot_key' => $key,
                    'quantity' => $quantity,
                    'unit_price' => $price,
                    'costs' => $costs,
                    'gross_cash_flow' => $gross,
                ];
                $this->insertHistory($userId, $openingId, 'STRUCTURED_POSITION_ROLLED', $openingHistory);
                foreach ($closingRows[$key] as $closed) {
                    $rolloverIds[] = $this->insertRolloverRecord($userId, $position, $closed, $openingId, $quantity, $slot['side'], $contract, $price, $costs);
                }
            }
            $this->recordAudit($userId, 'STRUCTURED_POSITION_ROLLED', (int) $position['id']);
            $this->pdo->commit();
        } catch (Throwable $exception) {
            if ($this->pdo->inTransaction()) $this->pdo->rollBack();
            throw $exception;
        }
        return ['success' => true, 'status' => 200, 'data' => [
            'position' => $this->find($userId, $uuid),
            'rollover_ids' => $rolloverIds,
        ]];
    }

    public function list(int $userId): array
    {
        return array_map([$this, 'present'], $this->positions->list($userId));
    }

    public function find(int $userId, string $uuid): ?array
    {
        $position = $this->positions->find($userId, $uuid);
        return $position === null ? null : $this->present($position);
    }

    public function archive(int $userId, string $uuid): array
    {
        $position = $this->positions->find($userId, $uuid);
        if ($position === null) return ['success' => false, 'status' => 404, 'message' => 'Posição estruturada não encontrada.'];

        try {
            $this->pdo->beginTransaction();
            if (!$this->positions->archive($userId, (int) $position['id'])) {
                $this->pdo->rollBack();
                return ['success' => false, 'status' => 409, 'message' => 'A posição já foi arquivada.'];
            }
            $this->recordAudit($userId, 'STRUCTURED_POSITION_ARCHIVED', (int) $position['id'], [
                'uuid' => $uuid,
                'note' => 'Posição ocultada do agrupamento; operações e histórico financeiro foram preservados.',
            ]);
            $this->pdo->commit();
        } catch (Throwable $exception) {
            if ($this->pdo->inTransaction()) $this->pdo->rollBack();
            throw $exception;
        }
        return ['success' => true, 'status' => 200, 'data' => ['uuid' => $uuid, 'archived' => true]];
    }

    public function unstructuredOperations(int $userId): array
    {
        return $this->positions->unstructuredOperations($userId);
    }

    private function present(array $position): array
    {
        $legs = [];
        $cashFlow = 0.0;
        $totalCosts = 0.0;
        $realized = 0.0;
        $unrealized = 0.0;
        $hasMissingQuote = false;
        $openCount = 0;
        $closedCount = 0;
        $openPayoffLegs = [];
        $nearestExpirations = [];
        foreach ($position['legs'] as $leg) {
            $quantity = (int) $leg['quantity'];
            $closedQuantity = min($quantity, (int) $leg['closed_quantity']);
            $openQuantity = max(0, $quantity - $closedQuantity);
            if ($leg['entry_kind'] === 'INITIAL') {
                $cashFlow += (float) $leg['gross_cash_flow'];
                $totalCosts += (float) $leg['costs'];
            }
            $openCount += $openQuantity;
            $closedCount += $closedQuantity;
            $realized += (float) ($leg['realized_result'] ?? 0);
            $quotePrice = $leg['quote_price'] === null ? null : (float) $leg['quote_price'];
            if ($openQuantity > 0 && $quotePrice === null) {
                $hasMissingQuote = true;
            } elseif ($openQuantity > 0) {
                $direction = $leg['side'] === 'BUY' ? 1 : -1;
                $unrealized += ($quotePrice - (float) $leg['unit_price']) * $direction * $openQuantity * (int) $leg['contract_multiplier'];
                $unrealized -= (float) $leg['costs'] * ($openQuantity / max(1, $quantity));
            }
            $entry = [
                'slot_key' => $leg['slot_key'],
                'entry_kind' => $leg['entry_kind'],
                'ratio' => (int) ($leg['ratio'] ?? 1),
                'label' => $leg['label'],
                'operation_id' => (int) $leg['id'],
                'option_contract_id' => (int) $leg['option_contract_id'],
                'contract_code' => $leg['contract_code'],
                'side' => $leg['side'],
                'option_type' => $leg['option_type'],
                'quantity' => $quantity,
                'closed_quantity' => $closedQuantity,
                'open_quantity' => $openQuantity,
                'strike_price' => (float) $leg['strike_price'],
                'expiration_date' => $leg['expiration_date'],
                'contract_multiplier' => (int) $leg['contract_multiplier'],
                'unit_price' => (float) $leg['unit_price'],
                'costs' => (float) $leg['costs'],
                'gross_cash_flow' => (float) $leg['gross_cash_flow'],
                'quote_price' => $quotePrice,
                'realized_result' => (float) ($leg['realized_result'] ?? 0),
                'description' => sprintf(
                    '%s %d %s %s, strike %s, venc. %s',
                    $leg['side'] === 'BUY' ? 'Comprar' : 'Vender',
                    $quantity,
                    $leg['option_type'] === 'CALL' ? 'Call' : 'Put',
                    $leg['contract_code'],
                    rtrim(rtrim(number_format((float) $leg['strike_price'], 6, '.', ''), '0'), '.'),
                    $this->formatDate((string) $leg['expiration_date'])
                ),
            ];
            $legs[] = $entry;
            if ($openQuantity > 0) {
                $payoffLeg = $leg;
                $payoffLeg['quantity'] = $openQuantity;
                $payoffLeg['costs'] = round((float) $leg['costs'] * ($openQuantity / max(1, $quantity)), 6);
                $payoffLeg['gross_cash_flow'] = round(
                    (float) $leg['unit_price'] * $openQuantity * (int) $leg['contract_multiplier'],
                    6
                ) * ($leg['side'] === 'BUY' ? -1 : 1);
                $openPayoffLegs[] = $payoffLeg;
            }
            if ($openQuantity > 0) $nearestExpirations[] = $leg['expiration_date'];
        }
        $status = $openCount === 0 ? 'CLOSED' : ($closedCount > 0 ? 'PARTIAL' : 'OPEN');
        $nearest = $nearestExpirations === [] ? null : min($nearestExpirations);
        $payoff = PositionPayoff::analyze($openPayoffLegs);
        return [
            'id' => (int) $position['id'],
            'uuid' => $position['uuid'],
            'name' => $position['name'],
            'strategy' => $position['declared_strategy'],
            'inferred_strategy' => $position['inferred_strategy'],
            'classification_status' => $position['classification_status'],
            'strategy_template_id' => (int) $position['strategy_template_id'],
            'support_level' => $position['support_level'],
            'asset_id' => (int) $position['asset_id'],
            'asset_symbol' => $position['asset_symbol'],
            'base_quantity' => $position['base_quantity'],
            'executed_at' => $position['executed_at'],
            'general_costs' => (float) $position['general_costs'],
            'currency' => $position['currency'],
            'broker' => $position['broker'],
            'status' => $status,
            'notes' => $position['notes'],
            'initial_cash_flow' => round($cashFlow, 6),
            'initial_costs' => round($totalCosts, 6),
            'initial_kind' => $cashFlow < 0 ? 'DEBIT' : ($cashFlow > 0 ? 'CREDIT' : 'ZERO'),
            'realized_result' => round($realized, 6),
            'unrealized_result' => $hasMissingQuote ? null : round($unrealized, 6),
            'nearest_expiration' => $nearest,
            'legs' => $legs,
            'payoff' => $payoff,
        ];
    }

    private function payoffLegs(array $slots, array $contractsBySlot, int $base): array
    {
        $legs = [];
        foreach ($slots as $slot) {
            $contract = $contractsBySlot[$slot['slot_key']];
            $quantity = $base * (int) $slot['ratio'];
            $price = (float) ($contract['unit_price'] ?? 0);
            $specificCosts = (float) ($contract['specific_costs'] ?? 0);
            $legs[] = [
                'side' => $slot['side'],
                'option_type' => $slot['option_type'],
                'strike_price' => (float) $contract['strike_price'],
                'expiration_date' => (string) $contract['expiration_date'],
                'quantity' => $quantity,
                'contract_multiplier' => (int) $contract['contract_multiplier'],
                'unit_price' => $price,
                'costs' => $specificCosts,
                'gross_cash_flow' => round($price * $quantity * (int) $contract['contract_multiplier'], 6) * ($slot['side'] === 'BUY' ? -1 : 1),
            ];
        }
        return $legs;
    }

    private function expectedRecognition(?string $code): ?string
    {
        return match ($code) {
            'long_call' => 'LONG_CALL',
            'short_call' => 'SHORT_CALL',
            'long_put' => 'LONG_PUT',
            'short_put' => 'SHORT_PUT',
            'call_spread_buy' => 'BULL_CALL_SPREAD',
            'call_spread_sell' => 'BEAR_CALL_SPREAD',
            'put_spread_buy' => 'BEAR_PUT_SPREAD',
            'put_spread_sell' => 'BULL_PUT_SPREAD',
            'long_straddle' => 'LONG_STRADDLE',
            'short_straddle' => 'SHORT_STRADDLE',
            'long_strangle' => 'LONG_STRANGLE',
            'short_strangle' => 'SHORT_STRANGLE',
            'long_call_fly' => 'LONG_CALL_BUTTERFLY',
            'long_put_fly' => 'LONG_PUT_BUTTERFLY',
            'iron_condor' => 'IRON_CONDOR',
            default => null,
        };
    }

    private function normalizeLegPrices(mixed $input, array $positionLegs, bool $roll): array
    {
        $errors = [];
        if (!is_array($input) || !array_is_list($input)) return ['errors' => ['legs' => 'Informe os preços de cada slot.'], 'legs' => []];
        $byKey = [];
        foreach ($input as $index => $leg) {
            $key = is_array($leg) ? (string) ($leg['slot_key'] ?? '') : '';
            if ($key === '' || isset($byKey[$key])) {
                $errors["legs.{$index}.slot_key"] = 'Slot ausente ou repetido.';
                continue;
            }
            $byKey[$key] = $leg;
        }
        $slotKeys = array_values(array_unique(array_column($positionLegs, 'slot_key')));
        foreach (array_diff($slotKeys, array_keys($byKey)) as $key) $errors["legs.{$key}"] = 'Informe preço e custos para o slot.';
        foreach (array_diff(array_keys($byKey), $slotKeys) as $key) $errors["legs.{$key}"] = 'Slot não pertence à posição.';
        foreach ($byKey as $key => $leg) {
            foreach (['unit_price', 'costs'] as $field) {
                if (!is_numeric($leg[$field] ?? null) || !is_finite((float) $leg[$field]) || (float) $leg[$field] < 0) {
                    $errors["legs.{$key}.{$field}"] = 'Informe valor numérico não negativo.';
                }
            }
            if ($roll && (!isset($leg['option_contract_id']) || filter_var($leg['option_contract_id'], FILTER_VALIDATE_INT) === false)) {
                $errors["legs.{$key}.option_contract_id"] = 'Selecione o novo contrato para o slot.';
            }
        }
        return ['errors' => $errors, 'legs' => $byKey];
    }

    private function closePlan(int $userId, array $position, int $lots): array
    {
        $template = $this->templates->findVisible($userId, (int) $position['strategy_template_id']);
        if ($template === null) return ['errors' => ['position' => 'Modelo da posição não está mais disponível.']];
        $rows = $this->positions->linkedOperations($userId, (int) $position['id']);
        $ratios = [];
        foreach ($template['slots'] as $slot) $ratios[$slot['slot_key']] = (int) $slot['ratio'];
        $groups = [];
        foreach ($rows as $row) {
            $open = max(0, (int) $row['quantity'] - (int) $row['closed_quantity']);
            if ($open > 0) $groups[$row['slot_key']][] = $row + ['open_quantity' => $open];
        }
        $availableLots = PHP_INT_MAX;
        foreach ($ratios as $key => $ratio) {
            $available = array_sum(array_column($groups[$key] ?? [], 'open_quantity'));
            if ($available % $ratio !== 0) return ['errors' => ['position' => 'As pernas abertas estão desalinhadas; encerre ou ajuste cada perna individualmente.']];
            $availableLots = min($availableLots, intdiv($available, $ratio));
        }
        if ($lots > $availableLots) return ['errors' => ['lots' => 'A quantidade solicitada excede os lotes completos ainda abertos.']];
        $allocations = [];
        foreach ($ratios as $key => $ratio) {
            $remaining = $lots * $ratio;
            foreach ($groups[$key] ?? [] as $opening) {
                if ($remaining === 0) break;
                $take = min($remaining, (int) $opening['open_quantity']);
                if ($take > 0) $allocations[$key][] = ['opening' => $opening, 'quantity' => $take];
                $remaining -= $take;
            }
            if ($remaining > 0) return ['errors' => ['position' => 'Não foi possível alocar as pernas abertas de forma proporcional.']];
        }
        return ['allocations' => $allocations];
    }

    private function recordClosures(int $userId, array $position, array $allocations, array $prices, string $action): array
    {
        $closingRows = [];
        foreach ($allocations as $slotKey => $items) {
            $totalQuantity = array_sum(array_column($items, 'quantity'));
            $totalCosts = (float) $prices[$slotKey]['costs'];
            $remainingCosts = $totalCosts;
            foreach ($items as $index => $item) {
                $opening = $item['opening'];
                $quantity = (int) $item['quantity'];
                $costs = $index === count($items) - 1 ? $remainingCosts : round($totalCosts * ($quantity / $totalQuantity), 6);
                $remainingCosts = round($remainingCosts - $costs, 6);
                $side = $opening['side'] === 'BUY' ? 'SELL' : 'BUY';
                $price = round((float) $prices[$slotKey]['unit_price'], 6);
                $gross = round($price * $quantity * (int) $opening['contract_multiplier'], 6) * ($side === 'BUY' ? -1 : 1);
                $closingId = $this->positions->insertOperation([
                    'user_id' => $userId,
                    'contract_id' => (int) $opening['option_contract_id'],
                    'strategy_id' => null,
                    'structured_id' => (int) $position['id'],
                    'side' => $side,
                    'quantity' => $quantity,
                    'unit_price' => $price,
                    'gross' => $gross,
                    'costs' => $costs,
                    'executed_at' => date('Y-m-d H:i:s'),
                    'operation_kind' => 'CLOSE',
                    'notes' => $action . ' — ' . $position['uuid'] . ' — ' . $slotKey,
                ]);
                $openingSign = $opening['side'] === 'BUY' ? 1 : -1;
                $realized = round(($price - (float) $opening['unit_price']) * $quantity * (int) $opening['contract_multiplier'] * $openingSign, 6);
                $realized -= $costs;
                $realized -= round((float) $opening['costs'] * ($quantity / max(1, (int) $opening['quantity'])), 6);
                $closure = $this->pdo->prepare('INSERT INTO operation_closures (user_id,opening_operation_id,closing_operation_id,quantity,realized_result) VALUES (:user_id,:opening_id,:closing_id,:quantity,:realized)');
                $closure->execute([
                    'user_id' => $userId,
                    'opening_id' => (int) $opening['id'],
                    'closing_id' => $closingId,
                    'quantity' => $quantity,
                    'realized' => $realized,
                ]);
                $after = [
                    'structured_position_uuid' => $position['uuid'],
                    'slot_key' => $slotKey,
                    'opening_operation_id' => (int) $opening['id'],
                    'quantity' => $quantity,
                    'unit_price' => $price,
                    'costs' => $costs,
                    'realized_result' => $realized,
                ];
                $this->insertHistory($userId, $closingId, $action, $after);
                $closingRows[$slotKey][] = [
                    'source_operation_id' => (int) $opening['id'],
                    'closing_operation_id' => $closingId,
                    'quantity' => $quantity,
                    'closing_contract_id' => (int) $opening['option_contract_id'],
                    'closing_cash_flow' => $gross,
                    'closing_costs' => $costs,
                    'realized_result' => $realized,
                ];
            }
        }
        return $closingRows;
    }

    private function insertRolloverRecord(int $userId, array $position, array $closed, int $openingId, int $openingQuantity, string $openingSide, array $contract, float $price, float $costs): int
    {
        $uuid = $this->uuid();
        $openingGross = round($price * (int) $closed['quantity'] * (int) $contract['contract_multiplier'], 6);
        $openingCash = $openingGross * ($openingSide === 'BUY' ? -1 : 1);
        $allocatedOpeningCosts = round($costs * ((int) $closed['quantity'] / max(1, $openingQuantity)), 6);
        $history = [
            'event' => 'STRUCTURED_POSITION_ROLLED',
            'structured_position_uuid' => $position['uuid'],
            'source_operation_id' => $closed['source_operation_id'],
            'closing_operation_id' => $closed['closing_operation_id'],
            'opening_operation_id' => $openingId,
            'quantity' => $closed['quantity'],
            'closing_cash_flow' => $closed['closing_cash_flow'],
            'opening_cash_flow' => $openingCash,
            'realized_result' => $closed['realized_result'],
        ];
        $query = $this->pdo->prepare('INSERT INTO rollovers (rollover_uuid,user_id,strategy_id,source_operation_id,closing_operation_id,opening_operation_id,quantity,closing_contract_id,opening_contract_id,closing_cash_flow,opening_cash_flow,net_cash_flow,costs,history) VALUES (:uuid,:user_id,NULL,:source_id,:closing_id,:opening_id,:quantity,:closing_contract,:opening_contract,:closing_flow,:opening_flow,:net_flow,:costs,:history)');
        $query->execute([
            'uuid' => $uuid,
            'user_id' => $userId,
            'source_id' => $closed['source_operation_id'],
            'closing_id' => $closed['closing_operation_id'],
            'opening_id' => $openingId,
            'quantity' => $closed['quantity'],
            'closing_contract' => $closed['closing_contract_id'],
            'opening_contract' => (int) $contract['id'],
            'closing_flow' => $closed['closing_cash_flow'],
            'opening_flow' => $openingCash,
            'net_flow' => round($closed['closing_cash_flow'] + $openingCash - $closed['closing_costs'] - $allocatedOpeningCosts, 6),
            'costs' => round($closed['closing_costs'] + $allocatedOpeningCosts, 6),
            'history' => json_encode($history, JSON_THROW_ON_ERROR),
        ]);
        return (int) $this->pdo->lastInsertId();
    }

    private function insertHistory(int $userId, int $operationId, string $action, array $after): void
    {
        $statement = $this->pdo->prepare('INSERT INTO operation_history (user_id,operation_id,action,before_data,after_data) VALUES (:user_id,:operation_id,:action,NULL,:after_data)');
        $statement->execute([
            'user_id' => $userId,
            'operation_id' => $operationId,
            'action' => $action,
            'after_data' => json_encode($after, JSON_THROW_ON_ERROR),
        ]);
    }

    private function recordAudit(int $userId, string $action, int $id, array $metadata = []): void
    {
        $statement = $this->pdo->prepare('INSERT INTO audit_logs (user_id,action,entity_type,entity_id,metadata) VALUES (:user_id,:action,:entity_type,:entity_id,:metadata)');
        $statement->execute([
            'user_id' => $userId,
            'action' => $action,
            'entity_type' => 'structured_position',
            'entity_id' => $id,
            'metadata' => $metadata === [] ? '{}' : json_encode($metadata, JSON_THROW_ON_ERROR),
        ]);
    }

    private function nonNegativeNumber(mixed $value, string $field, array &$errors): float
    {
        if (!is_numeric($value) || !is_finite((float) $value) || (float) $value < 0 || (float) $value > 999999999999.999999) {
            $errors[$field] = 'Informe um valor numérico não negativo compatível com a precisão monetária.';
            return 0.0;
        }
        return (float) $value;
    }

    private function validDateTime(string $value): bool
    {
        $parsed = DateTimeImmutable::createFromFormat('!Y-m-d H:i:s', $value);
        $errors = DateTimeImmutable::getLastErrors();
        return $parsed !== false && ($errors === false || ($errors['warning_count'] === 0 && $errors['error_count'] === 0))
            && $parsed->format('Y-m-d H:i:s') === $value;
    }

    private function normalizeDateTime(string $value): string
    {
        $value = str_replace('T', ' ', $value);
        if (preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}$/', $value)) $value .= ':00';
        return $value;
    }

    private function formatDate(string $value): string
    {
        $date = DateTimeImmutable::createFromFormat('!Y-m-d', substr($value, 0, 10));
        return $date === false ? $value : $date->format('d/m/Y');
    }

    private function uuid(): string
    {
        $bytes = random_bytes(16);
        $bytes[6] = chr((ord($bytes[6]) & 0x0f) | 0x40);
        $bytes[8] = chr((ord($bytes[8]) & 0x3f) | 0x80);
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($bytes), 4));
    }

    private function fail(array $errors, int $status = 422): array
    {
        return ['success' => false, 'status' => $status, 'errors' => $errors];
    }
}
