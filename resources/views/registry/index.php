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
        .button-secondary { background: #e2e8f0; color: #0f172a; }
        .status { padding: 12px 14px; border-radius: 8px; margin-bottom: 18px; font-weight: 600; }
        .leg-stack { display: grid; gap: 12px; }
        .leg-row { display: grid; grid-template-columns: repeat(auto-fit, minmax(120px, 1fr)); gap: 10px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 12px; }
        .leg-row .mini-label { display: grid; gap: 6px; font-size: 12px; font-weight: 600; }
        .finance-summary { display:grid; grid-template-columns:repeat(auto-fit,minmax(150px,1fr)); gap:10px; margin-top:14px; }
        .finance-metric { background:#f8fafc; border:1px solid #e2e8f0; border-radius:8px; padding:12px; }
        .finance-metric span { display:block; color:#475569; font-size:12px; margin-bottom:6px; }
        .finance-metric strong { font-size:18px; }
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
                <form method="POST" action="/registros/estrategias" id="strategy-form">
                    <input type="hidden" name="_csrf_token" value="<?php echo htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8'); ?>">
                    <input type="hidden" name="strategy_id" id="strategy-id" value="">
                    <label>
                        Nome
                        <input type="text" name="name" id="strategy-name" placeholder="Estratégia de alta" required>
                    </label>
                    <label>
                        Descrição
                        <textarea name="description" id="strategy-description" placeholder="Descrição da ideia da estratégia"></textarea>
                    </label>
                    <div>
                        <div style="display:flex; justify-content:space-between; align-items:center; gap:12px; margin-bottom:8px;">
                            <strong>Pernas</strong>
                            <div style="display:flex; gap:8px;">
                                <button type="button" class="button-secondary" id="add-leg">Adicionar perna</button>
                                <button type="button" class="button-secondary" id="reset-strategy-form">Nova estratégia</button>
                            </div>
                        </div>
                        <div id="strategy-legs" class="leg-stack"></div>
                        <div class="finance-summary" id="strategy-financial-summary" aria-live="polite">
                            <div class="finance-metric"><span>Débito bruto (compras)</span><strong id="strategy-debit">R$ 0,00</strong></div>
                            <div class="finance-metric"><span>Crédito bruto (vendas)</span><strong id="strategy-credit">R$ 0,00</strong></div>
                            <div class="finance-metric"><span>Custos</span><strong id="strategy-costs">R$ 0,00</strong></div>
                            <div class="finance-metric"><span>Fluxo líquido inicial</span><strong id="strategy-net-flow">R$ 0,00</strong></div>
                        </div>
                        <p class="muted" id="strategy-financial-note">Estimativa baseada nas pernas preenchidas. Não representa lucro final nem payoff no vencimento.</p>
                    </div>
                    <label>
                        Status
                        <select name="status" id="strategy-status">
                            <option value="ACTIVE">Ativa</option>
                            <option value="INACTIVE">Inativa</option>
                        </select>
                    </label>
                    <button type="submit" id="strategy-submit">Salvar estratégia</button>
                </form>

                <div style="margin-top: 20px;">
                    <strong>Estratégias salvas</strong>
                    <div style="display:grid; gap:8px; margin-top:10px;">
                        <?php if (!$strategies): ?>
                            <span class="muted">Nenhuma estratégia cadastrada.</span>
                        <?php else: ?>
                            <?php foreach ($strategies as $strategy): ?>
                                <button
                                    type="button"
                                    class="button-secondary load-strategy"
                                    data-id="<?php echo (int) ($strategy['id'] ?? 0); ?>"
                                    data-name="<?php echo htmlspecialchars((string) ($strategy['name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"
                                    data-description="<?php echo htmlspecialchars((string) ($strategy['description'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"
                                    data-status="<?php echo htmlspecialchars((string) ($strategy['status'] ?? 'ACTIVE'), ENT_QUOTES, 'UTF-8'); ?>"
                                    data-legs='<?php echo json_encode((array) json_decode((string) ($strategy['legs'] ?? '[]'), true), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>'>
                                    <?php echo htmlspecialchars((string) ($strategy['name'] ?? 'Estratégia'), ENT_QUOTES, 'UTF-8'); ?>
                                </button>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <script>
                    (() => {
                        const form = document.getElementById('strategy-form');
                        const container = document.getElementById('strategy-legs');
                        const addButton = document.getElementById('add-leg');
                        const resetButton = document.getElementById('reset-strategy-form');
                        const strategyIdInput = document.getElementById('strategy-id');
                        const submitButton = document.getElementById('strategy-submit');
                        const contracts = <?php echo json_encode(array_values($contracts), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
                        const currency = value => new Intl.NumberFormat('pt-BR', {style:'currency', currency:'BRL'}).format(Number.isFinite(value) ? value : 0);

                        const createRow = (index, leg = {}) => {
                            const row = document.createElement('div');
                            row.className = 'leg-row';
                            const side = (leg.side ?? 'BUY');
                            const optionType = (leg.option_type ?? 'CALL');
                            const strike = Number(leg.strike_price ?? 100);
                            const expiration = leg.expiration_date ?? new Date().toISOString().slice(0, 10);
                            const quantity = Number(leg.quantity ?? 1);
                            const premium = Number(leg.premium ?? 0);
                            const multiplier = Number(leg.contract_multiplier ?? 100);
                            const costs = Number(leg.costs ?? 0);
                            const contractId = String(leg.option_contract_id ?? '');
                            const contractOptions = '<option value="">Selecione o contrato</option>' + contracts.map(contract => '<option value="' + Number(contract.id) + '" ' + (String(contract.id) === contractId ? 'selected' : '') + '>' + String(contract.code || ('Contrato #' + contract.id)).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;') + '</option>').join('');

                            row.innerHTML = `
                                <input type="hidden" name="legs[${index}][position]" value="${index + 1}">
                                <label class="mini-label">
                                    Contrato
                                    <select name="legs[${index}][option_contract_id]" class="leg-contract">${contractOptions}</select>
                                </label>
                                <label class="mini-label">
                                    Lado
                                    <select name="legs[${index}][side]">
                                        <option value="BUY" ${side === 'BUY' ? 'selected' : ''}>Compra</option>
                                        <option value="SELL" ${side === 'SELL' ? 'selected' : ''}>Venda</option>
                                    </select>
                                </label>
                                <label class="mini-label">
                                    Tipo
                                    <select name="legs[${index}][option_type]">
                                        <option value="CALL" ${optionType === 'CALL' ? 'selected' : ''}>CALL</option>
                                        <option value="PUT" ${optionType === 'PUT' ? 'selected' : ''}>PUT</option>
                                    </select>
                                </label>
                                <label class="mini-label">
                                    Strike
                                    <input type="number" step="0.01" min="0.01" name="legs[${index}][strike_price]" value="${strike}" required>
                                </label>
                                <label class="mini-label">
                                    Vencimento
                                    <input type="date" name="legs[${index}][expiration_date]" value="${expiration}" required>
                                </label>
                                <label class="mini-label">
                                    Quantidade
                                    <input type="number" min="1" step="1" name="legs[${index}][quantity]" value="${quantity}" required>
                                </label>
                                <label class="mini-label">
                                    Prêmio
                                    <input type="number" step="0.01" min="0" name="legs[${index}][premium]" value="${premium}" required>
                                </label>
                                <label class="mini-label">
                                    Mult.
                                    <input type="number" min="1" step="1" name="legs[${index}][contract_multiplier]" value="${multiplier}" required>
                                </label>
                                <label class="mini-label">
                                    Custos
                                    <input type="number" step="0.01" min="0" name="legs[${index}][costs]" value="${costs}">
                                </label>
                                <div style="display:flex; align-items:end;">
                                    <button type="button" class="button-secondary remove-leg" style="width:100%;">Remover</button>
                                </div>
                            `;

                            const contractSelect = row.querySelector('.leg-contract');
                            contractSelect.addEventListener('change', () => {
                                const contract = contracts.find(item => String(item.id) === contractSelect.value);
                                if (contract) {
                                    row.querySelector('[name$="[option_type]"]').value = contract.option_type || 'CALL';
                                    row.querySelector('[name$="[strike_price]"]').value = contract.strike_price ?? '';
                                    row.querySelector('[name$="[expiration_date]"]').value = contract.expiration_date || '';
                                    row.querySelector('[name$="[contract_multiplier]"]').value = contract.contract_multiplier || 100;
                                }
                                updateFinancialSummary();
                            });
                            row.querySelectorAll('input,select').forEach(field => field.addEventListener('input', updateFinancialSummary));
                            row.querySelector('.remove-leg').addEventListener('click', () => {
                                if (container.children.length > 1) {
                                    row.remove();
                                    syncRowPositions();
                                    updateFinancialSummary();
                                }
                            });

                            return row;
                        };

                        const syncRowPositions = () => {
                            const rows = Array.from(container.querySelectorAll('.leg-row'));
                            rows.forEach((row, index) => {
                                row.querySelectorAll('input, select').forEach((field) => {
                                    const name = field.name;
                                    if (!name) return;
                                    const nextName = name.replace(/legs\[\d+\]/, `legs[${index}]`);
                                    field.name = nextName;
                                });
                                const hidden = row.querySelector('input[type="hidden"]');
                                if (hidden) hidden.value = index + 1;
                            });
                        };


                        const updateFinancialSummary = () => {
                            let debit = 0, credit = 0, costs = 0;
                            container.querySelectorAll('.leg-row').forEach(row => {
                                const value = selector => Number(row.querySelector(selector)?.value || 0);
                                const side = row.querySelector('[name$="[side]"]')?.value;
                                const gross = value('[name$="[quantity]"]') * value('[name$="[premium]"]') * value('[name$="[contract_multiplier]"]');
                                if (side === 'BUY') debit += gross;
                                if (side === 'SELL') credit += gross;
                                costs += value('[name$="[costs]"]');
                            });
                            const net = credit - debit - costs;
                            document.getElementById('strategy-debit').textContent = currency(debit);
                            document.getElementById('strategy-credit').textContent = currency(credit);
                            document.getElementById('strategy-costs').textContent = currency(costs);
                            const netElement = document.getElementById('strategy-net-flow');
                            netElement.textContent = currency(net);
                            netElement.style.color = net < 0 ? '#b45309' : net > 0 ? '#047857' : '#0f172a';
                        };

                        const addLeg = (leg = {}) => {
                            const index = container.children.length;
                            container.appendChild(createRow(index, leg));
                        };

                        const renderStrategy = (payload = []) => {
                            container.innerHTML = '';
                            if (!payload.length) {
                                addLeg();
                                return;
                            }
                            payload.forEach((leg) => addLeg(leg));
                            updateFinancialSummary();
                        };

                        const resetStrategyForm = () => {
                            strategyIdInput.value = '';
                            submitButton.textContent = 'Salvar estratégia';
                            document.getElementById('strategy-name').value = '';
                            document.getElementById('strategy-description').value = '';
                            document.getElementById('strategy-status').value = 'ACTIVE';
                            renderStrategy([]);
                            updateFinancialSummary();
                        };

                        addButton.addEventListener('click', () => addLeg());
                        resetButton.addEventListener('click', resetStrategyForm);

                        document.querySelectorAll('.load-strategy').forEach((button) => {
                            button.addEventListener('click', () => {
                                const legs = JSON.parse(button.dataset.legs || '[]');
                                document.getElementById('strategy-name').value = button.dataset.name || '';
                                document.getElementById('strategy-description').value = button.dataset.description || '';
                                document.getElementById('strategy-status').value = button.dataset.status || 'ACTIVE';
                                strategyIdInput.value = button.dataset.id || '';
                                submitButton.textContent = 'Atualizar estratégia';
                                renderStrategy(legs);
                            });
                        });

                        form.addEventListener('submit', () => {
                            if (!strategyIdInput.value) {
                                submitButton.textContent = 'Salvar estratégia';
                            }
                        });

                        renderStrategy([]);
                    })();
                </script>
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
