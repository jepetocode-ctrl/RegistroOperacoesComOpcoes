<?php
/** @var array $user */
/** @var array $assets */
/** @var array $contracts */
/** @var array $strategies */
/** @var array $operations */
/** @var string|null $error */
/** @var string|null $success */
/** @var string $csrf */
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registros</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 0; background: #f4f7fb; color: #1b2a41; }
        .topbar { background: #102a43; color: #fff; padding: 16px 24px; display: flex; justify-content: space-between; align-items: center; }
        .topbar a { color: #fff; text-decoration: none; margin-left: 12px; }
        .container { max-width: 1200px; margin: 24px auto; padding: 0 16px 32px; }
        .grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 20px; }
        .card { background: #fff; border-radius: 10px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); padding: 20px; }
        .card h2 { margin-top: 0; }
        form { display: grid; gap: 12px; }
        label { display: grid; gap: 6px; font-size: 14px; font-weight: 600; }
        input, select, textarea, button { font: inherit; }
        input, select, textarea { width: 100%; padding: 10px 12px; box-sizing: border-box; border: 1px solid #cbd5e1; border-radius: 8px; }
        textarea { min-height: 100px; resize: vertical; }
        button { cursor: pointer; background: #2563eb; color: #fff; border: none; border-radius: 8px; padding: 10px 16px; }
        .status { padding: 12px 14px; border-radius: 8px; margin-bottom: 18px; font-weight: 600; }
        .status.error { background: #fee2e2; color: #991b1b; }
        .status.success { background: #dcfce7; color: #166534; }
        table { width: 100%; border-collapse: collapse; margin-top: 16px; font-size: 13px; }
        th, td { border-bottom: 1px solid #e2e8f0; padding: 8px 6px; text-align: left; vertical-align: top; }
        th { background: #f8fafc; }
        .muted { color: #475569; }
    </style>
</head>
<body>
    <div class="topbar">
        <div><strong>Registro de Ativos e Posições</strong></div>
        <nav>
            <a href="/dashboard">Dashboard</a>
            <a href="/logout">Sair</a>
        </nav>
    </div>

    <div class="container">
        <?php if (!empty($error)): ?>
            <div class="status error"><?php echo htmlspecialchars((string) $error, ENT_QUOTES, 'UTF-8'); ?></div>
        <?php endif; ?>
        <?php if (!empty($success)): ?>
            <div class="status success"><?php echo htmlspecialchars((string) $success, ENT_QUOTES, 'UTF-8'); ?></div>
        <?php endif; ?>

        <h1>Registros do usuário: <?php echo htmlspecialchars((string) ($user['username'] ?? 'Usuário'), ENT_QUOTES, 'UTF-8'); ?></h1>
        <p class="muted">Todos os dados abaixo são isolados por usuário e não são compartilhados com outras contas.</p>

        <div class="grid">
            <section class="card">
                <h2>Cadastrar ativo</h2>
                <form method="POST" action="/registros/ativos">
                    <input type="hidden" name="_csrf_token" value="<?php echo htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8'); ?>">
                    <label>
                        Símbolo
                        <input type="text" name="symbol" placeholder="PETR4" required>
                    </label>
                    <label>
                        Nome
                        <input type="text" name="name" placeholder="Petrobras PN" required>
                    </label>
                    <label>
                        Tipo
                        <select name="asset_type">
                            <option value="STOCK">Ação</option>
                            <option value="ETF">ETF</option>
                            <option value="INDEX">Índice</option>
                            <option value="CURRENCY">Moeda</option>
                            <option value="FUTURE">Futuro</option>
                        </select>
                    </label>
                    <label>
                        Status
                        <select name="status">
                            <option value="ACTIVE">Ativo</option>
                            <option value="INACTIVE">Inativo</option>
                        </select>
                    </label>
                    <button type="submit">Salvar ativo</button>
                </form>
            </section>

            <section class="card">
                <h2>Cadastrar contrato</h2>
                <form method="POST" action="/registros/contratos">
                    <input type="hidden" name="_csrf_token" value="<?php echo htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8'); ?>">
                    <label>
                        Ativo
                        <select name="asset_id" required>
                            <option value="">Selecione</option>
                            <?php foreach ($assets as $asset): ?>
                                <option value="<?php echo (int) $asset['id']; ?>"><?php echo htmlspecialchars((string) $asset['symbol'], ENT_QUOTES, 'UTF-8'); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <label>
                        Código
                        <input type="text" name="code" placeholder="PETR4 2025-10" required>
                    </label>
                    <label>
                        Tipo de opção
                        <select name="option_type" required>
                            <option value="CALL">CALL</option>
                            <option value="PUT">PUT</option>
                        </select>
                    </label>
                    <label>
                        Preço de exercício
                        <input type="number" step="0.01" min="0.01" name="strike_price" required>
                    </label>
                    <label>
                        Vencimento
                        <input type="date" name="expiration_date" required>
                    </label>
                    <label>
                        Multiplicador
                        <input type="number" step="1" min="1" name="contract_multiplier" value="100" required>
                    </label>
                    <label>
                        Status
                        <select name="status">
                            <option value="ACTIVE">Ativo</option>
                            <option value="INACTIVE">Inativo</option>
                        </select>
                    </label>
                    <button type="submit">Salvar contrato</button>
                </form>
            </section>

            <section class="card">
                <h2>Cadastrar estratégia</h2>
                <form method="POST" action="/registros/estrategias">
                    <input type="hidden" name="_csrf_token" value="<?php echo htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8'); ?>">
                    <label>
                        Nome
                        <input type="text" name="name" placeholder="Estratégia de alta" required>
                    </label>
                    <label>
                        Descrição
                        <textarea name="description" placeholder="Descrição da ideia da estratégia"></textarea>
                    </label>
                    <label>
                        Pernas (JSON)
                        <textarea name="legs" placeholder='[{"side":"BUY","contract_code":"PETR4 2025-10"}]'></textarea>
                    </label>
                    <label>
                        Status
                        <select name="status">
                            <option value="ACTIVE">Ativa</option>
                            <option value="INACTIVE">Inativa</option>
                        </select>
                    </label>
                    <button type="submit">Salvar estratégia</button>
                </form>
            </section>

            <section class="card">
                <h2>Registrar posição</h2>
                <form method="POST" action="/registros/operacoes">
                    <input type="hidden" name="_csrf_token" value="<?php echo htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8'); ?>">
                    <label>
                        Contrato
                        <select name="option_contract_id" required>
                            <option value="">Selecione</option>
                            <?php foreach ($contracts as $contract): ?>
                                <option value="<?php echo (int) $contract['id']; ?>"><?php echo htmlspecialchars((string) ($contract['code'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <label>
                        Estratégia
                        <select name="strategy_id">
                            <option value="">Sem estratégia</option>
                            <?php foreach ($strategies as $strategy): ?>
                                <option value="<?php echo (int) $strategy['id']; ?>"><?php echo htmlspecialchars((string) $strategy['name'], ENT_QUOTES, 'UTF-8'); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <label>
                        Lado
                        <select name="side" required>
                            <option value="BUY">Compra</option>
                            <option value="SELL">Venda</option>
                        </select>
                    </label>
                    <label>
                        Quantidade
                        <input type="number" min="1" step="1" name="quantity" value="1" required>
                    </label>
                    <label>
                        Preço unitário
                        <input type="number" step="0.01" min="0" name="unit_price" required>
                    </label>
                    <label>
                        Custos
                        <input type="number" step="0.01" min="0" name="costs" value="0">
                    </label>
                    <label>
                        Tipo de operação
                        <select name="operation_kind">
                            <option value="TRADE">Trade</option>
                            <option value="OPEN">Abertura</option>
                            <option value="CLOSE">Fechamento</option>
                            <option value="PARTIAL_CLOSE">Fechamento parcial</option>
                            <option value="REVERSAL">Inversão</option>
                            <option value="ADJUSTMENT">Ajuste</option>
                        </select>
                    </label>
                    <label>
                        Data da operação
                        <input type="datetime-local" name="executed_at" value="<?php echo date('Y-m-d\TH:i'); ?>" required>
                    </label>
                    <label>
                        Observações
                        <textarea name="notes" placeholder="Notas e observações"></textarea>
                    </label>
                    <button type="submit">Registrar posição</button>
                </form>
            </section>
        </div>

        <div class="card" style="margin-top: 28px;">
            <h2>Registros recentes</h2>
            <table>
                <thead>
                    <tr>
                        <th>Tipo</th>
                        <th>Detalhe</th>
                        <th>Quantidade</th>
                        <th>Preço</th>
                        <th>Data</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!$operations): ?>
                        <tr><td colspan="5" class="muted">Nenhuma operação cadastrada para este usuário.</td></tr>
                    <?php else: ?>
                        <?php foreach ($operations as $operation): ?>
                            <tr>
                                <td><?php echo htmlspecialchars((string) ($operation['operation_kind'] ?? 'TRADE'), ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><?php echo htmlspecialchars((string) ($operation['side'] ?? 'BUY'), ENT_QUOTES, 'UTF-8'); ?> · contrato <?php echo (int) ($operation['option_contract_id'] ?? 0); ?></td>
                                <td><?php echo (int) ($operation['quantity'] ?? 0); ?></td>
                                <td><?php echo number_format((float) ($operation['unit_price'] ?? 0), 2, ',', '.'); ?></td>
                                <td><?php echo htmlspecialchars((string) ($operation['executed_at'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>
