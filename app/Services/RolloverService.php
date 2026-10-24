<?php

declare(strict_types=1);

namespace App\Services;

use PDO;
use Throwable;

/** Atomic rollover registration; cash flow is not automatically realized profit. */
final class RolloverService
{
    public function __construct(private readonly PDO $pdo) {}

    public function register(int $userId, array $input): array
    {
        $errors = $this->validate($input);
        if ($errors !== []) return ['success'=>false, 'status'=>422, 'errors'=>$errors];

        try {
            $this->pdo->beginTransaction();
            $sourceId = (int)$input['source_operation_id'];
            $quantity = (int)$input['quantity'];
            $closeContractId = (int)$input['closing_contract_id'];
            $openContractId = (int)$input['opening_contract_id'];
            $strategyId = isset($input['strategy_id']) && $input['strategy_id'] !== '' ? (int)$input['strategy_id'] : null;

            $q = $this->pdo->prepare('SELECT * FROM operations WHERE id = :id AND user_id = :user_id');
            $q->execute(['id'=>$sourceId, 'user_id'=>$userId]);
            $source = $q->fetch(PDO::FETCH_ASSOC);
            if (!$source) return $this->reject(['source_operation_id'=>'Operação de origem não encontrada.']);
            $structuredPositionId = $source['structured_position_id'] ?? null;
            $structuredSlot = null;
            $structuredDefinition = null;
            if ($structuredPositionId !== null) {
                $slotQuery = $this->pdo->prepare('SELECT slot_key FROM structured_position_legs WHERE structured_position_id = :position_id AND operation_id = :operation_id');
                $slotQuery->execute(['position_id' => $structuredPositionId, 'operation_id' => $sourceId]);
                $structuredSlot = $slotQuery->fetchColumn();
                if ($structuredSlot === false) return $this->reject(['source_operation_id'=>'A rolagem deve partir de uma perna de abertura vinculada à posição estruturada.']);
                $definitionQuery = $this->pdo->prepare('SELECT sp.asset_id, s.side, s.option_type FROM structured_positions sp JOIN strategy_template_slots s ON s.template_id = sp.strategy_template_id AND s.slot_key = :slot_key WHERE sp.id = :position_id AND sp.user_id = :user_id');
                $definitionQuery->execute(['slot_key' => $structuredSlot, 'position_id' => $structuredPositionId, 'user_id' => $userId]);
                $structuredDefinition = $definitionQuery->fetch(PDO::FETCH_ASSOC);
                if (!$structuredDefinition) return $this->reject(['source_operation_id'=>'A definição da perna estruturada não está disponível.']);
            }
            if ((int)$source['option_contract_id'] !== $closeContractId) return $this->reject(['closing_contract_id'=>'O contrato de encerramento deve corresponder à operação de origem.']);
            if ($closeContractId === $openContractId) return $this->reject(['opening_contract_id'=>'A rolagem exige contrato de abertura diferente do contrato encerrado.']);

            $q = $this->pdo->prepare('SELECT id, asset_id, option_type, contract_multiplier FROM option_contracts WHERE user_id = :user_id AND status = :status AND id IN (:close_id, :open_id)');
            $q->execute(['user_id'=>$userId, 'status'=>'ACTIVE', 'close_id'=>$closeContractId, 'open_id'=>$openContractId]);
            $rows = $q->fetchAll(PDO::FETCH_ASSOC);
            if (count($rows) !== 2) return $this->reject(['contract'=>'Os dois contratos devem existir e estar ativos.']);
            $multipliers = [];
            foreach ($rows as $row) $multipliers[(int)$row['id']] = (int)$row['contract_multiplier'];

            if ($strategyId !== null) {
                $q = $this->pdo->prepare('SELECT id FROM strategies WHERE id = :id AND user_id = :user_id');
                $q->execute(['id'=>$strategyId, 'user_id'=>$userId]);
                if (!$q->fetchColumn()) return $this->reject(['strategy_id'=>'Estratégia inexistente.']);
            } else {
                $strategyId = $source['strategy_id'] === null ? null : (int)$source['strategy_id'];
                if ($strategyId !== null) {
                    $q = $this->pdo->prepare('SELECT id FROM strategies WHERE id = :id AND user_id = :user_id');
                    $q->execute(['id'=>$strategyId, 'user_id'=>$userId]);
                    if (!$q->fetchColumn()) return $this->reject(['strategy_id'=>'Estratégia da operação de origem inválida.']);
                }
            }

            $q = $this->pdo->prepare('SELECT (SELECT COALESCE(SUM(quantity),0) FROM operation_closures WHERE user_id = :user_id_a AND opening_operation_id = :source_id_a) + (SELECT COALESCE(SUM(quantity),0) FROM operation_closures WHERE user_id = :user_id_b AND closing_operation_id = :source_id_b)');
            $q->execute(['user_id_a'=>$userId,'source_id_a'=>$sourceId,'user_id_b'=>$userId,'source_id_b'=>$sourceId]);
            $remaining = (int)$source['quantity'] - (int)$q->fetchColumn();
            if ($quantity > $remaining) return $this->reject(['quantity'=>'A quantidade excede a posição remanescente da operação de origem.']);

            $closeSide = $source['side'] === 'BUY' ? 'SELL' : 'BUY';
            $openSide = strtoupper((string)($input['opening_side'] ?? $source['side']));
            if ($structuredDefinition !== null) {
                $openingContract = null;
                foreach ($rows as $row) {
                    if ((int) $row['id'] === $openContractId) $openingContract = $row;
                }
                if ($openSide !== $structuredDefinition['side']) return $this->reject(['opening_side'=>'A rolagem deve preservar o lado definido pelo slot estruturado.']);
                if ($openingContract === null
                    || (int) $openingContract['asset_id'] !== (int) $structuredDefinition['asset_id']
                    || strtoupper((string) $openingContract['option_type']) !== strtoupper((string) $structuredDefinition['option_type'])) {
                    return $this->reject(['opening_contract_id'=>'O novo contrato deve manter ativo e tipo de opção do slot estruturado.']);
                }
            }
            $closePrice = (float)$input['closing_unit_price'];
            $openPrice = (float)$input['opening_unit_price'];
            $closeCosts = (float)($input['closing_costs'] ?? 0);
            $openCosts = (float)($input['opening_costs'] ?? 0);
            $totalCosts = round($closeCosts + $openCosts, 6);
            $closeFlow = round($closePrice * $quantity * $multipliers[$closeContractId] * ($closeSide === 'BUY' ? -1 : 1), 6);
            $openFlow = round($openPrice * $quantity * $multipliers[$openContractId] * ($openSide === 'BUY' ? -1 : 1), 6);
            $netFlow = round($closeFlow + $openFlow - $totalCosts, 6);
            $at = (string)($input['executed_at'] ?? date('Y-m-d H:i:s'));
            $uuid = $this->uuid();

            $closingId = $this->insertOperation($userId,$closeContractId,$strategyId,$closeSide,$quantity,$closePrice,$closeFlow,$closeCosts,$at,$sourceId,'CLOSE','Rolagem '.$uuid.' — encerramento',$structuredPositionId === null ? null : (int) $structuredPositionId);
            $direction = $source['side'] === 'BUY' ? 1 : -1;
            $realized = round(($closePrice - (float)$source['unit_price']) * $quantity * $multipliers[$closeContractId] * $direction, 6);
            $realized -= $closeCosts;
            $realized -= round((float)$source['costs'] * ($quantity / max(1, (int)$source['quantity'])), 6);
            $q = $this->pdo->prepare('INSERT INTO operation_closures (user_id, opening_operation_id, closing_operation_id, quantity, realized_result) VALUES (:user_id,:opening_id,:closing_id,:quantity,:realized)');
            $q->execute(['user_id'=>$userId,'opening_id'=>$sourceId,'closing_id'=>$closingId,'quantity'=>$quantity,'realized'=>$realized]);

            $openingId = $this->insertOperation($userId,$openContractId,$strategyId,$openSide,$quantity,$openPrice,$openFlow,$openCosts,$at,$closingId,'OPEN','Rolagem '.$uuid.' — abertura',$structuredPositionId === null ? null : (int) $structuredPositionId);
            if ($structuredPositionId !== null && is_string($structuredSlot)) {
                $link = $this->pdo->prepare("INSERT INTO structured_position_legs (structured_position_id,slot_key,entry_kind,operation_id,option_contract_id) VALUES (:position_id,:slot_key,'ROLLED',:operation_id,:contract_id)");
                $link->execute([
                    'position_id' => (int) $structuredPositionId,
                    'slot_key' => $structuredSlot,
                    'operation_id' => $openingId,
                    'contract_id' => $openContractId,
                ]);
            }
            $history = [
                'event'=>'ROLLOVER_REGISTERED','rollover_uuid'=>$uuid,'source_operation_id'=>$sourceId,
                'closing_operation_id'=>$closingId,'opening_operation_id'=>$openingId,'strategy_id'=>$strategyId,'quantity'=>$quantity,
                'closing'=>['contract_id'=>$closeContractId,'side'=>$closeSide,'unit_price'=>$closePrice,'costs'=>$closeCosts,'gross_cash_flow'=>$closeFlow],
                'opening'=>['contract_id'=>$openContractId,'side'=>$openSide,'unit_price'=>$openPrice,'costs'=>$openCosts,'gross_cash_flow'=>$openFlow],
                'net_cash_flow'=>$netFlow,'costs'=>$totalCosts,
                'note'=>'Crédito/débito líquido é fluxo de caixa, não lucro automático.'
            ];
            $historyJson = json_encode($history, JSON_THROW_ON_ERROR);
            $q = $this->pdo->prepare('INSERT INTO rollovers (rollover_uuid,user_id,strategy_id,source_operation_id,closing_operation_id,opening_operation_id,quantity,closing_contract_id,opening_contract_id,closing_cash_flow,opening_cash_flow,net_cash_flow,costs,history) VALUES (:uuid,:user_id,:strategy_id,:source_id,:closing_id,:opening_id,:quantity,:close_contract,:open_contract,:close_flow,:open_flow,:net_flow,:costs,:history)');
            $q->execute(['uuid'=>$uuid,'user_id'=>$userId,'strategy_id'=>$strategyId,'source_id'=>$sourceId,'closing_id'=>$closingId,'opening_id'=>$openingId,'quantity'=>$quantity,'close_contract'=>$closeContractId,'open_contract'=>$openContractId,'close_flow'=>$closeFlow,'open_flow'=>$openFlow,'net_flow'=>$netFlow,'costs'=>$totalCosts,'history'=>$historyJson]);
            $this->insertHistory($userId,$closingId,$history);
            $this->insertHistory($userId,$openingId,$history);
            $this->pdo->commit();

            return ['success'=>true,'status'=>201,'data'=>[
                'rollover_uuid'=>$uuid,'strategy_id'=>$strategyId,'source_operation_id'=>$sourceId,
                'closing_operation_id'=>$closingId,'opening_operation_id'=>$openingId,'quantity'=>$quantity,
                'closing_cash_flow'=>$closeFlow,'opening_cash_flow'=>$openFlow,'net_cash_flow'=>$netFlow,
                'costs'=>$totalCosts,'realized_result'=>$realized,
                'note'=>'Crédito/débito líquido é fluxo de caixa, não lucro automático.'
            ]];
        } catch (Throwable $e) {
            if ($this->pdo->inTransaction()) $this->pdo->rollBack();
            throw $e;
        }
    }

    public function history(int $userId, ?string $uuid = null): array
    {
        $sql = 'SELECT * FROM rollovers WHERE user_id = :user_id';
        $params = ['user_id'=>$userId];
        if ($uuid !== null) { $sql .= ' AND rollover_uuid = :uuid'; $params['uuid'] = $uuid; }
        $sql .= ' ORDER BY id DESC';
        $q = $this->pdo->prepare($sql);
        $q->execute($params);
        return $q->fetchAll(PDO::FETCH_ASSOC);
    }

    private function insertOperation(int $userId,int $contractId,?int $strategyId,string $side,int $quantity,float $price,float $flow,float $costs,string $at,?int $referenceId,string $kind,string $notes,?int $structuredPositionId = null): int
    {
        $hasStructuredColumn = $this->columnExists('operations', 'structured_position_id');
        $sql = 'INSERT INTO operations (user_id,option_contract_id,strategy_id,side,quantity,unit_price,gross_cash_flow,costs,executed_at,reference_operation_id,operation_kind,notes';
        if ($hasStructuredColumn) $sql .= ',structured_position_id';
        $sql .= ') VALUES (:user_id,:contract_id,:strategy_id,:side,:quantity,:price,:flow,:costs,:at,:reference_id,:kind,:notes';
        if ($hasStructuredColumn) $sql .= ',:structured_id';
        $sql .= ')';
        $q = $this->pdo->prepare($sql);
        $params = ['user_id'=>$userId,'contract_id'=>$contractId,'strategy_id'=>$strategyId,'side'=>$side,'quantity'=>$quantity,'price'=>$price,'flow'=>$flow,'costs'=>$costs,'at'=>$at,'reference_id'=>$referenceId,'kind'=>$kind,'notes'=>$notes];
        if ($hasStructuredColumn) $params['structured_id'] = $structuredPositionId;
        $q->execute($params);
        return (int)$this->pdo->lastInsertId();
    }

    private function columnExists(string $table, string $column): bool
    {
        if ($this->pdo->getAttribute(PDO::ATTR_DRIVER_NAME) === 'sqlite') {
            foreach ($this->pdo->query("PRAGMA table_info({$table})")->fetchAll(PDO::FETCH_ASSOC) as $row) {
                if ($row['name'] === $column) return true;
            }
            return false;
        }
        $query = $this->pdo->prepare('SELECT 1 FROM information_schema.columns WHERE table_name = :table AND column_name = :column');
        $query->execute(['table' => $table, 'column' => $column]);
        return (bool) $query->fetchColumn();
    }

    private function insertHistory(int $userId,int $operationId,array $history): void
    {
        $q = $this->pdo->prepare('INSERT INTO operation_history (user_id,operation_id,action,before_data,after_data) VALUES (:user_id,:operation_id,:action,NULL,:after_data)');
        $q->execute(['user_id'=>$userId,'operation_id'=>$operationId,'action'=>'ROLLOVER_REGISTERED','after_data'=>json_encode($history,JSON_THROW_ON_ERROR)]);
    }

    private function reject(array $errors): array
    {
        if ($this->pdo->inTransaction()) $this->pdo->rollBack();
        return ['success'=>false,'status'=>422,'errors'=>$errors];
    }

    private function validate(array $input): array
    {
        $errors = [];
        foreach (['source_operation_id','closing_contract_id','opening_contract_id','quantity','closing_unit_price','opening_unit_price'] as $key) if (!isset($input[$key]) || $input[$key] === '') $errors[$key]='Campo obrigatório.';
        foreach (['source_operation_id','closing_contract_id','opening_contract_id','quantity'] as $key) if (isset($input[$key]) && (filter_var($input[$key],FILTER_VALIDATE_INT) === false || (int)$input[$key] < 1)) $errors[$key]='Informe um inteiro positivo.';
        foreach (['closing_unit_price','opening_unit_price','closing_costs','opening_costs'] as $key) if (isset($input[$key]) && (!is_numeric($input[$key]) || (float)$input[$key] < 0)) $errors[$key]='Informe um valor numérico não negativo.';
        if (isset($input['opening_side']) && !in_array(strtoupper((string)$input['opening_side']),['BUY','SELL'],true)) $errors['opening_side']='Use BUY ou SELL.';
        if (isset($input['executed_at']) && strtotime((string)$input['executed_at']) === false) $errors['executed_at']='Data/hora inválida.';
        return $errors;
    }

    private function uuid(): string
    {
        $bytes = random_bytes(16);
        $bytes[6] = chr((ord($bytes[6]) & 0x0f) | 0x40);
        $bytes[8] = chr((ord($bytes[8]) & 0x3f) | 0x80);
        $hex = bin2hex($bytes);
        return sprintf('%s-%s-%s-%s-%s',substr($hex,0,8),substr($hex,8,4),substr($hex,12,4),substr($hex,16,4),substr($hex,20,12));
    }
}
