<?php
/** @var array $user */
/** @var array $assets */
/** @var array $contracts */
/** @var array $strategies */
/** @var array $operations */
/** @var array $strategyTemplates */
/** @var array $structuredPositions */
/** @var string|null $error */
/** @var string|null $success */
/** @var string $csrf */
/** @var string|null $formContext */
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
        .slot-fields { display:grid; grid-template-columns:repeat(auto-fit,minmax(135px,1fr)); gap:10px; align-items:end; }
        .template-slot,.position-slot { display:grid; gap:10px; padding:14px; border:1px solid #dbe4f0; border-radius:10px; background:#f8fafc; }
        .derived { color:#475569; font-size:12px; font-weight:500; }
        .actions-row { display:flex; gap:8px; flex-wrap:wrap; align-items:center; }
        .checkline { display:flex; grid-template-columns:auto 1fr; align-items:start; gap:9px; font-weight:500; }
        .checkline input { width:auto; margin-top:3px; }
        .template-list { display:flex; gap:8px; flex-wrap:wrap; margin-top:12px; }
        .template-card { display:grid; gap:6px; border:1px solid #dbe4f0; padding:10px; border-radius:8px; min-width:180px; }
        .structured-list { display:grid; gap:10px; }
        .structured-row { border:1px solid #dbe4f0; border-radius:8px; padding:12px; }
        @media (max-width:640px) { .container { margin:12px auto; } .card { padding:16px; } .topbar { padding:14px 16px; } }
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
                            <?php foreach (is_array($assets) ? $assets : [] as $asset): ?>
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
                <h2>Modelos de estratégia</h2>
                <p class="muted">Um modelo define slots, lado, tipo, proporção e restrições — não armazena contratos nem negociações reais.</p>
                <form method="POST" action="/registros/modelos" id="template-form">
                    <input type="hidden" name="_csrf_token" value="<?php echo htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8'); ?>">
                    <input type="hidden" name="template_id" id="template-id" value="">
                    <label>Começar pelo catálogo
                        <select id="template-source">
                            <option value="">Montar modelo livre</option>
                            <?php foreach ($strategyTemplates as $template): ?>
                                <?php if ($template['user_id'] === null && !$template['needs_review']): ?>
                                    <option value="<?php echo (int) $template['id']; ?>"><?php echo htmlspecialchars((string) $template['name'], ENT_QUOTES, 'UTF-8'); ?> · copiar</option>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <label>Nome do modelo
                        <input name="name" id="template-name" maxlength="120" required>
                    </label>
                    <label>Descrição
                        <textarea name="description" id="template-description" rows="2"></textarea>
                    </label>
                    <div>
                        <div class="actions-row">
                            <strong>Slots</strong>
                            <button type="button" class="button-secondary" id="add-template-slot">Adicionar slot</button>
                        </div>
                        <div id="template-slots" class="leg-stack"></div>
                    </div>
                    <div>
                        <div class="actions-row">
                            <strong>Restrições entre slots</strong>
                            <button type="button" class="button-secondary" id="add-template-constraint">Adicionar restrição</button>
                        </div>
                        <div id="template-constraints" class="leg-stack"></div>
                    </div>
                    <button type="submit" id="template-submit">Salvar modelo</button>
                </form>
                <div class="template-list">
                    <?php foreach ($strategyTemplates as $template): ?>
                        <div class="template-card">
                            <strong><?php echo htmlspecialchars((string) $template['name'], ENT_QUOTES, 'UTF-8'); ?></strong>
                            <span class="muted"><?php echo htmlspecialchars((string) ($template['origin'] ?? ''), ENT_QUOTES, 'UTF-8'); ?> · <?php echo htmlspecialchars((string) $template['support_level'], ENT_QUOTES, 'UTF-8'); ?><?php echo $template['needs_review'] ? ' · exige revisão' : ''; ?></span>
                            <button type="button" class="button-secondary load-template" data-id="<?php echo (int) $template['id']; ?>">Usar modelo</button>
                        </div>
                    <?php endforeach; ?>
                </div>
            </section>

            <section class="card">
                <h2>Registrar posição estruturada</h2>
                <p class="muted">Informe os dados comuns uma vez. Lado, tipo e quantidade são derivados do modelo; strike, vencimento e multiplicador vêm do contrato.</p>
                <form method="POST" action="/registros/posicoes-estruturadas" id="structured-position-form">
                    <input type="hidden" name="_csrf_token" value="<?php echo htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8'); ?>">
                    <label>Modelo de estratégia
                        <select name="strategy_template_id" id="position-template" required>
                            <option value="">Selecione um modelo compatível</option>
                            <?php foreach ($strategyTemplates as $template): ?>
                                <option value="<?php echo (int) $template['id']; ?>" <?php echo $template['needs_review'] || $template['support_level'] === 'UNSUPPORTED' ? 'disabled' : ''; ?>>
                                    <?php echo htmlspecialchars((string) $template['name'], ENT_QUOTES, 'UTF-8'); ?><?php echo $template['needs_review'] ? ' · exige revisão' : ($template['support_level'] === 'UNSUPPORTED' ? ' · não suportado' : ''); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <div class="slot-fields">
                        <label>Ativo-objeto
                            <select name="asset_id" id="position-asset" required>
                                <option value="">Selecione</option>
                                <?php foreach ($assets as $asset): ?>
                                    <option value="<?php echo (int) $asset['id']; ?>"><?php echo htmlspecialchars((string) $asset['symbol'], ENT_QUOTES, 'UTF-8'); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </label>
                        <label>Quantidade-base
                            <input type="number" min="1" max="100000" step="1" name="base_quantity" id="position-base" value="1" required>
                        </label>
                        <label>Execução
                            <input type="datetime-local" name="executed_at" id="position-executed-at" value="<?php echo date('Y-m-d\\TH:i'); ?>" required>
                        </label>
                        <label>Moeda
                            <input name="currency" id="position-currency" value="BRL" maxlength="3" required>
                        </label>
                        <label>Corretora
                            <input name="broker" id="position-broker" maxlength="120">
                        </label>
                        <label>Custos gerais
                            <input type="number" name="general_costs" id="position-general-costs" min="0" step="0.000001" value="0">
                        </label>
                    </div>
                    <label>Nome da posição (opcional)
                        <input name="name" id="position-name" maxlength="120" placeholder="Usa o nome do modelo se ficar vazio">
                    </label>
                    <div id="position-slots" class="leg-stack"><p class="muted">Escolha o modelo para abrir os campos de cada perna.</p></div>
                    <label>Observações
                        <textarea name="notes" id="position-notes" rows="2"></textarea>
                    </label>
                    <label class="checkline">
                        <input type="checkbox" name="confirm_classification" value="1" id="confirm-classification">
                        Confirmo o registro mesmo se a classificação inferida divergir ou não puder ser reconhecida.
                    </label>
                    <div class="finance-summary" aria-live="polite">
                        <div class="finance-metric"><span>Débito bruto</span><strong id="position-debit">—</strong></div>
                        <div class="finance-metric"><span>Crédito bruto</span><strong id="position-credit">—</strong></div>
                        <div class="finance-metric"><span>Custos estimados</span><strong id="position-costs">—</strong></div>
                        <div class="finance-metric"><span>Fluxo inicial (não é lucro)</span><strong id="position-flow">—</strong></div>
                    </div>
                    <p id="position-preview" class="muted" aria-live="polite">A prévia é estimativa; backend revalida titularidade, proporções e restrições antes de gravar.</p>
                    <button type="submit">Registrar todas as pernas</button>
                </form>
            </section>

            <details class="card">
                <summary>Compatibilidade: estratégias legadas</summary>
            <section class="card">
                <h2>Editar cadastro antigo de estratégia</h2>
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
                        <?php if (!is_array($strategies) || !$strategies): ?>
                            <span class="muted">Nenhuma estratégia cadastrada.</span>
                        <?php else: ?>
                            <?php foreach ($strategies as $strategy): ?>
                                <?php
                                $strategyLegs = $strategy['legs'] ?? [];
                                if (is_string($strategyLegs)) {
                                    $strategyLegs = json_decode($strategyLegs, true);
                                }
                                $strategyLegs = is_array($strategyLegs) ? array_values($strategyLegs) : [];
                                ?>
                                <button
                                    type="button"
                                    class="button-secondary load-strategy"
                                    data-id="<?php echo (int) ($strategy['id'] ?? 0); ?>"
                                    data-name="<?php echo htmlspecialchars((string) ($strategy['name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"
                                    data-description="<?php echo htmlspecialchars((string) ($strategy['description'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"
                                    data-status="<?php echo htmlspecialchars((string) ($strategy['status'] ?? 'ACTIVE'), ENT_QUOTES, 'UTF-8'); ?>"
                                    data-legs='<?php echo json_encode($strategyLegs, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>'>
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
                        const contractList = <?php echo json_encode(array_values((array) $contracts), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
                        const contracts = Array.isArray(contractList) ? contractList : [];
                        const parseLegsPayload = (value) => {
                            if (!value) {
                                return [];
                            }

                            try {
                                const parsed = JSON.parse(value);
                                return Array.isArray(parsed) ? parsed : [];
                            } catch (error) {
                                return [];
                            }
                        };
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
                            const contractOptions = '<option value="">Selecione o contrato</option>' + (Array.isArray(contracts) ? contracts.map(contract => '<option value="' + Number(contract.id) + '" ' + (String(contract.id) === contractId ? 'selected' : '') + '>' + String(contract.code || ('Contrato #' + contract.id)).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;') + '</option>').join('') : '');

                            row.innerHTML = `
                                <input type="hidden" name="legs[${index}][position]" value="${index + 1}">
                                <label class="mini-label">
                                    Contrato
                                    <select name="legs[${index}][option_contract_id]" class="leg-contract" required>${contractOptions}</select>
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
                                const legs = parseLegsPayload(button.dataset.legs || '[]');
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
            </details>

            <section class="card">
                <h2>Registrar operação avulsa</h2>
                <form method="POST" action="/registros/operacoes">
                    <input type="hidden" name="_csrf_token" value="<?php echo htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8'); ?>">
                    <label>
                        Contrato
                        <select name="option_contract_id" required>
                            <option value="">Selecione</option>
                            <?php foreach (is_array($contracts) ? $contracts : [] as $contract): ?>
                                <option value="<?php echo (int) $contract['id']; ?>"><?php echo htmlspecialchars((string) ($contract['code'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></option>
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

        <section class="card" style="margin-top:28px;">
            <h2>Posições estruturadas registradas</h2>
            <?php if ($structuredPositions === []): ?>
                <p class="muted">Nenhuma posição estruturada registrada ainda. Registre uma pela ficha acima.</p>
            <?php else: ?>
                <div class="structured-list">
                    <?php foreach ($structuredPositions as $position): ?>
                        <div class="structured-row">
                            <strong><?php echo htmlspecialchars((string) $position['strategy'], ENT_QUOTES, 'UTF-8'); ?> · <?php echo htmlspecialchars((string) $position['asset_symbol'], ENT_QUOTES, 'UTF-8'); ?></strong>
                            <span class="pill"><?php echo htmlspecialchars((string) $position['status'], ENT_QUOTES, 'UTF-8'); ?></span>
                            <p class="muted"><?php echo htmlspecialchars((string) $position['name'], ENT_QUOTES, 'UTF-8'); ?> · <?php echo (int) $position['base_quantity']; ?> lote(s)-base · fluxo inicial <?php echo htmlspecialchars((string) $position['initial_kind'], ENT_QUOTES, 'UTF-8'); ?> <?php echo number_format((float) $position['initial_cash_flow'], 2, ',', '.'); ?></p>
                            <details>
                                <summary>Ver pernas e classificação</summary>
                                <p>Declarada: <?php echo htmlspecialchars((string) $position['strategy'], ENT_QUOTES, 'UTF-8'); ?> · Inferida: <?php echo htmlspecialchars((string) ($position['inferred_strategy'] ?? $position['classification_status']), ENT_QUOTES, 'UTF-8'); ?></p>
                                <ul><?php foreach ($position['legs'] as $leg): ?><li><?php echo htmlspecialchars((string) $leg['description'], ENT_QUOTES, 'UTF-8'); ?> · aberta: <?php echo (int) $leg['open_quantity']; ?></li><?php endforeach; ?></ul>
                            </details>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>

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
                    <?php if (!is_array($operations) || !$operations): ?>
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
    <script>
        (() => {
            'use strict';
            const templates = <?php echo json_encode(array_values((array) $strategyTemplates), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
            const contracts = <?php echo json_encode(array_values((array) $contracts), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
            const oldInput = <?php echo json_encode(is_array($oldInput ?? null) ? $oldInput : [], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
            const formContext = <?php echo json_encode($formContext ?? null, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
            const ownUserId = <?php echo (int) ($user['id'] ?? 0); ?>;
            const escapeHtml = value => String(value ?? '').replace(/[&<>"']/g, char => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[char]));
            const money = value => new Intl.NumberFormat('pt-BR', {style:'currency', currency:'BRL'}).format(Number(value) || 0);

            const templateForm = document.getElementById('template-form');
            const templateSlots = document.getElementById('template-slots');
            const templateConstraints = document.getElementById('template-constraints');
            let templateSlotCount = 0;
            let templateConstraintCount = 0;
            const slotNames = () => Array.from(templateSlots.querySelectorAll('[name$="[slot_key]"]')).map(input => input.value).filter(Boolean);

            function addTemplateSlot(slot = {}) {
                const index = templateSlotCount++;
                const row = document.createElement('div');
                row.className = 'template-slot';
                row.innerHTML = `
                    <div class="slot-fields">
                        <label class="mini-label">Chave
                            <input name="slots[${index}][slot_key]" value="${escapeHtml(slot.slot_key || 'slot_' + (index + 1))}" pattern="[a-z][a-z0-9_]{0,39}" required>
                        </label>
                        <label class="mini-label">Rótulo
                            <input name="slots[${index}][label]" value="${escapeHtml(slot.label || '')}" maxlength="120" required>
                        </label>
                        <label class="mini-label">Instrumento
                            <select name="slots[${index}][instrument_type]" class="slot-instrument">
                                <option value="OPTION" ${slot.instrument_type !== 'ACTION' ? 'selected' : ''}>Opção</option>
                                <option value="ACTION" ${slot.instrument_type === 'ACTION' ? 'selected' : ''}>Ativo-objeto (não registrável ainda)</option>
                            </select>
                        </label>
                        <label class="mini-label">Lado
                            <select name="slots[${index}][side]">
                                <option value="BUY" ${slot.side !== 'SELL' ? 'selected' : ''}>Compra</option>
                                <option value="SELL" ${slot.side === 'SELL' ? 'selected' : ''}>Venda</option>
                            </select>
                        </label>
                        <label class="mini-label">Tipo
                            <select name="slots[${index}][option_type]" class="slot-option-type">
                                <option value="CALL" ${slot.option_type !== 'PUT' ? 'selected' : ''}>Call</option>
                                <option value="PUT" ${slot.option_type === 'PUT' ? 'selected' : ''}>Put</option>
                            </select>
                        </label>
                        <label class="mini-label">Proporção
                            <input type="number" name="slots[${index}][ratio]" min="1" max="100" step="1" value="${Number(slot.ratio || 1)}" required>
                        </label>
                        <div class="actions-row">
                            <button type="button" class="button-secondary move-template-slot" data-direction="-1" aria-label="Mover slot para cima">↑</button>
                            <button type="button" class="button-secondary move-template-slot" data-direction="1" aria-label="Mover slot para baixo">↓</button>
                            <button type="button" class="button-secondary duplicate-template-slot">Duplicar</button>
                            <button type="button" class="button-secondary remove-template-slot">Remover</button>
                        </div>
                    </div>`;
                const instrument = row.querySelector('.slot-instrument');
                const type = row.querySelector('.slot-option-type');
                const keyInput = row.querySelector('[name$="[slot_key]"]');
                let previousKey = keyInput.value;
                keyInput.addEventListener('change', () => {
                    templateConstraints.querySelectorAll('.constraint-slots option').forEach(option => {
                        if (option.value === previousKey) option.value = keyInput.value;
                    });
                    previousKey = keyInput.value;
                    refreshConstraintSlotOptions();
                });
                const sync = () => { type.disabled = instrument.value === 'ACTION'; };
                instrument.addEventListener('change', sync);
                row.querySelectorAll('.move-template-slot').forEach(button => button.addEventListener('click', () => {
                    const sibling = Number(button.dataset.direction) < 0 ? row.previousElementSibling : row.nextElementSibling;
                    if (!sibling) return;
                    if (Number(button.dataset.direction) < 0) templateSlots.insertBefore(row, sibling);
                    else templateSlots.insertBefore(sibling, row);
                }));
                row.querySelector('.duplicate-template-slot').addEventListener('click', () => {
                    const copy = Object.fromEntries(Array.from(row.querySelectorAll('input,select')).map(field => [field.name.match(/\]\[([^\]]+)\]$/)?.[1], field.value]));
                    const existing = new Set(slotNames());
                    let key = String(copy.slot_key || 'slot').slice(0, 33) + '_copy';
                    let suffix = 2;
                    while (existing.has(key)) key = String(copy.slot_key || 'slot').slice(0, 30) + '_copy_' + suffix++;
                    copy.slot_key = key;
                    addTemplateSlot(copy);
                });
                row.querySelector('.remove-template-slot').addEventListener('click', () => {
                    row.remove();
                    refreshConstraintSlotOptions();
                });
                templateSlots.appendChild(row);
                sync();
                refreshConstraintSlotOptions();
            }

            function refreshConstraintSlotOptions() {
                const names = slotNames();
                templateConstraints.querySelectorAll('.constraint-slots').forEach(select => {
                    const selected = Array.from(select.selectedOptions).map(option => option.value);
                    select.innerHTML = names.map(key => `<option value="${escapeHtml(key)}" ${selected.includes(key) ? 'selected' : ''}>${escapeHtml(key)}</option>`).join('');
                });
            }

            function addConstraint(constraint = {}) {
                const index = templateConstraintCount++;
                const row = document.createElement('div');
                row.className = 'template-slot slot-fields';
                const selected = Array.isArray(constraint.slots) ? constraint.slots : [];
                const names = slotNames();
                row.innerHTML = `
                    <label class="mini-label">Regra
                        <select name="constraints[${index}][type]">
                            ${[['strike_order','Ordem de strikes'],['same_strike','Mesmo strike'],['same_expiration','Mesmo vencimento'],['different_expiration','Vencimentos diferentes'],['same_asset','Mesmo ativo-objeto']].map(([value,label]) => `<option value="${value}" ${constraint.type === value ? 'selected' : ''}>${label}</option>`).join('')}
                        </select>
                    </label>
                    <label class="mini-label">Slots (Ctrl/⌘ para selecionar vários)
                        <select multiple class="constraint-slots" name="constraints[${index}][slots][]">
                            ${names.map(key => `<option value="${escapeHtml(key)}" ${selected.includes(key) ? 'selected' : ''}>${escapeHtml(key)}</option>`).join('')}
                        </select>
                    </label>
                    <button type="button" class="button-secondary remove-template-constraint">Remover regra</button>`;
                row.querySelector('.remove-template-constraint').addEventListener('click', () => row.remove());
                templateConstraints.appendChild(row);
            }

            function loadTemplate(template, editing = false) {
                document.getElementById('template-id').value = editing && template.user_id === ownUserId ? template.id : '';
                document.getElementById('template-name').value = editing ? template.name : template.name + ' (meu modelo)';
                document.getElementById('template-description').value = template.description || '';
                document.getElementById('template-submit').textContent = editing && template.user_id === ownUserId ? 'Salvar alterações' : 'Salvar cópia como modelo';
                templateSlots.innerHTML = '';
                templateConstraints.innerHTML = '';
                templateSlotCount = 0;
                templateConstraintCount = 0;
                (template.slots || []).forEach(addTemplateSlot);
                (template.constraints || []).forEach(addConstraint);
            }

            document.getElementById('add-template-slot').addEventListener('click', () => addTemplateSlot());
            document.getElementById('add-template-constraint').addEventListener('click', () => addConstraint());
            document.getElementById('template-source').addEventListener('change', event => {
                const template = templates.find(item => Number(item.id) === Number(event.target.value));
                if (template) loadTemplate(template, false);
                else {
                    document.getElementById('template-id').value = '';
                    document.getElementById('template-name').value = '';
                    document.getElementById('template-description').value = '';
                    document.getElementById('template-submit').textContent = 'Salvar modelo';
                    templateSlots.innerHTML = '';
                    templateConstraints.innerHTML = '';
                    templateSlotCount = 0;
                    templateConstraintCount = 0;
                }
            });
            document.querySelectorAll('.load-template').forEach(button => button.addEventListener('click', () => {
                const template = templates.find(item => Number(item.id) === Number(button.dataset.id));
                if (template) loadTemplate(template, true);
                templateForm.scrollIntoView({behavior:'smooth', block:'start'});
            }));
            if (formContext === 'template' && Array.isArray(oldInput.slots)) {
                document.getElementById('template-id').value = oldInput.template_id || '';
                document.getElementById('template-name').value = oldInput.name || '';
                document.getElementById('template-description').value = oldInput.description || '';
                templateSlots.innerHTML = '';
                templateConstraints.innerHTML = '';
                templateSlotCount = 0;
                templateConstraintCount = 0;
                oldInput.slots.forEach(addTemplateSlot);
                (oldInput.constraints || []).forEach(addConstraint);
            }

            const positionTemplate = document.getElementById('position-template');
            const positionSlots = document.getElementById('position-slots');
            const assetSelect = document.getElementById('position-asset');
            function renderPositionSlots() {
                const template = templates.find(item => Number(item.id) === Number(positionTemplate.value));
                if (!template) {
                    positionSlots.innerHTML = '<p class="muted">Escolha o modelo para abrir os campos de cada perna.</p>';
                    updatePositionPreview();
                    return;
                }
                positionSlots.innerHTML = '';
                (template.slots || []).forEach((slot, index) => {
                    const row = document.createElement('div');
                    row.className = 'position-slot';
                    if (slot.instrument_type !== 'OPTION') {
                        row.innerHTML = `<strong>${escapeHtml(slot.label)}</strong><p class="muted">Ativo-objeto ainda não tem suporte no ledger; este modelo não pode ser registrado.</p>`;
                        positionSlots.appendChild(row);
                        return;
                    }
                    const options = contracts.map(contract => `<option value="${Number(contract.id)}" data-asset="${Number(contract.asset_id)}" data-type="${escapeHtml(contract.option_type)}">${escapeHtml(contract.code)}</option>`).join('');
                    row.innerHTML = `
                        <input type="hidden" name="legs[${index}][slot_key]" value="${escapeHtml(slot.slot_key)}">
                        <strong>${escapeHtml(slot.label)}</strong>
                        <span class="derived">${slot.side === 'BUY' ? 'Compra' : 'Venda'} de ${escapeHtml(slot.option_type)} · quantidade = base × ${Number(slot.ratio)}</span>
                        <div class="slot-fields">
                            <label class="mini-label">Contrato
                                <select name="legs[${index}][option_contract_id]" class="position-contract" required>
                                    <option value="">Selecione contrato</option>${options}
                                </select>
                            </label>
                            <label class="mini-label">Preço de execução
                                <input type="number" name="legs[${index}][unit_price]" min="0" step="0.000001" required>
                            </label>
                            <label class="mini-label">Custos desta perna
                                <input type="number" name="legs[${index}][costs]" min="0" step="0.000001" value="0">
                            </label>
                        </div>
                        <span class="derived contract-derived">Strike, vencimento e multiplicador serão derivados do contrato escolhido.</span>
                        <span class="derived position-description"></span>`;
                    const contractSelect = row.querySelector('.position-contract');
                    contractSelect.addEventListener('change', () => {
                        const contract = contracts.find(item => Number(item.id) === Number(contractSelect.value));
                        row.querySelector('.contract-derived').textContent = contract
                            ? `Strike ${contract.strike_price} · venc. ${contract.expiration_date} · multiplicador ${contract.contract_multiplier}`
                            : 'Strike, vencimento e multiplicador serão derivados do contrato escolhido.';
                        updatePositionPreview();
                    });
                    row.querySelectorAll('input').forEach(input => input.addEventListener('input', updatePositionPreview));
                    positionSlots.appendChild(row);
                });
                filterPositionContracts();
                updatePositionPreview();
            }

            function filterPositionContracts() {
                const assetId = Number(assetSelect.value);
                positionSlots.querySelectorAll('.position-contract').forEach(select => {
                    const row = select.closest('.position-slot');
                    const typeText = row.querySelector('.derived').textContent;
                    const requiredType = typeText.includes('CALL') ? 'CALL' : 'PUT';
                    Array.from(select.options).forEach(option => {
                        if (!option.value) return;
                        option.hidden = Number(option.dataset.asset) !== assetId || option.dataset.type !== requiredType;
                        if (option.selected && option.hidden) select.value = '';
                    });
                });
                updatePositionPreview();
            }

            function updatePositionPreview() {
                let debit = 0;
                let credit = 0;
                let costs = Number(document.getElementById('position-general-costs').value) || 0;
                const base = Number(document.getElementById('position-base').value) || 0;
                const descriptions = [];
                positionSlots.querySelectorAll('.position-slot').forEach(row => {
                    const select = row.querySelector('.position-contract');
                    if (!select) return;
                    const slotKey = row.querySelector('input[type="hidden"]').value;
                    const template = templates.find(item => Number(item.id) === Number(positionTemplate.value));
                    const slot = (template?.slots || []).find(item => item.slot_key === slotKey);
                    const contract = contracts.find(item => Number(item.id) === Number(select.value));
                    const price = Number(row.querySelector('[name$="[unit_price]"]').value) || 0;
                    const legCosts = Number(row.querySelector('[name$="[costs]"]').value) || 0;
                    costs += legCosts;
                    if (!slot || !contract) return;
                    const quantity = base * Number(slot.ratio);
                    const amount = price * quantity * Number(contract.contract_multiplier);
                    if (slot.side === 'BUY') debit += amount; else credit += amount;
                    descriptions.push(`${slot.side === 'BUY' ? 'Comprar' : 'Vender'} ${quantity} ${slot.option_type} ${contract.code}, strike ${contract.strike_price}, venc. ${contract.expiration_date}`);
                    row.querySelector('.position-description').textContent = descriptions[descriptions.length - 1];
                });
                const net = credit - debit;
                document.getElementById('position-debit').textContent = money(debit);
                document.getElementById('position-credit').textContent = money(credit);
                document.getElementById('position-costs').textContent = money(costs);
                document.getElementById('position-flow').textContent = `${net < 0 ? 'Débito' : net > 0 ? 'Crédito' : 'Neutro'} · ${money(net)}`;
                document.getElementById('position-preview').textContent = descriptions.length
                    ? descriptions.join(' · ')
                    : 'Selecione um contrato para cada perna. O fluxo é caixa inicial, não lucro.';
            }
            positionTemplate.addEventListener('change', renderPositionSlots);
            assetSelect.addEventListener('change', filterPositionContracts);
            ['position-base','position-general-costs'].forEach(id => document.getElementById(id).addEventListener('input', updatePositionPreview));

            if (oldInput.strategy_template_id) {
                positionTemplate.value = String(oldInput.strategy_template_id);
                renderPositionSlots();
                const fieldIds = {asset_id:'position-asset',base_quantity:'position-base',executed_at:'position-executed-at',currency:'position-currency',broker:'position-broker',general_costs:'position-general-costs',name:'position-name',notes:'position-notes'};
                for (const key of Object.keys(fieldIds)) {
                    const field = document.getElementById(fieldIds[key]);
                    if (field && oldInput[key] !== undefined) field.value = oldInput[key];
                }
                if (oldInput.confirm_classification) document.getElementById('confirm-classification').checked = true;
                filterPositionContracts();
                (oldInput.legs || []).forEach((leg, index) => {
                    const row = positionSlots.querySelectorAll('.position-slot')[index];
                    if (!row) return;
                    for (const key of ['option_contract_id','unit_price','costs']) {
                        const field = row.querySelector(`[name$="[${key}]"]`);
                        if (field && leg[key] !== undefined) field.value = leg[key];
                    }
                });
                updatePositionPreview();
            }
        })();
    </script>
</body>
</html>
