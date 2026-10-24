<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Dashboard | Registro de Operações com Opções</title>
    <style>
        :root { color-scheme: light; }
        body { margin: 0; font-family: Arial, sans-serif; background: #f8fafc; color: #0f172a; }
        .topbar { background: #0f172a; color: white; padding: 18px 24px; display: flex; justify-content: space-between; align-items: center; gap: 16px; flex-wrap: wrap; }
        .brand { font-weight: 700; }
        .actions, .tabs { display: flex; align-items: center; gap: 12px; flex-wrap: wrap; }
        .card { max-width: 1100px; margin: 24px auto; background: white; border-radius: 16px; box-shadow: 0 12px 30px rgba(15, 23, 42, 0.08); padding: 24px; }
        .logout-form { display: inline; }
        button { border: 0; border-radius: 8px; padding: 10px 14px; cursor: pointer; font: inherit; }
        .logout-form button { background: #dc2626; color: white; }
        .status { display: inline-block; background: #ecfdf5; color: #065f46; padding: 8px 12px; border-radius: 999px; font-weight: 700; margin-bottom: 16px; }
        .tabs button { background: #e2e8f0; color: #0f172a; }
        .tabs button[aria-selected="true"] { background: #0f172a; color: white; }
        .fields { display: grid; grid-template-columns: repeat(auto-fit,minmax(190px,1fr)); gap: 16px; margin: 20px 0; }
        label { display: flex; flex-direction: column; gap: 6px; font-size: .94rem; }
        input, select { padding: 10px; border: 1px solid #cbd5e1; border-radius: 8px; min-width: 0; font: inherit; }
        .primary { background: #2563eb; color: white; }
        .result { margin-top: 18px; padding: 16px; border-radius: 10px; background: #f1f5f9; line-height: 1.6; overflow-wrap: anywhere; }
        .error { background: #fff1f2; color: #9f1239; }
        .muted { color: #475569; font-size: .92rem; }
        [hidden] { display: none !important; }
        @media (max-width: 600px) { .card { margin: 12px; padding: 16px; } .topbar { padding: 16px; } }

        :root { color-scheme: light; --ink:#172033; --muted:#64748b; --line:#e2e8f0; --bg:#f5f7fb; --accent:#3659e3; }
        body { background:var(--bg); color:var(--ink); font-family:Inter,ui-sans-serif,system-ui,-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif; }
        .topbar { background:#101a30; padding:14px 24px; position:sticky; top:0; z-index:10; }
        .brand { letter-spacing:-.02em; }
        .app-shell { display:grid; grid-template-columns:230px minmax(0,1fr); gap:24px; max-width:1500px; margin:24px auto; padding:0 24px; align-items:start; }
        .sidebar { background:#fff; border:1px solid var(--line); border-radius:16px; padding:18px 12px; position:sticky; top:90px; }
        .side-label { padding:8px 12px; color:var(--muted); font-size:.72rem; text-transform:uppercase; letter-spacing:.12em; font-weight:800; }
        .side-nav { display:grid; gap:5px; }
        .side-nav a { display:flex; align-items:center; gap:10px; text-decoration:none; color:#475569; border-radius:9px; padding:11px 12px; font-weight:650; font-size:.92rem; }
        .side-nav a:hover,.side-nav a[aria-current="page"] { color:#243da8; background:#eef2ff; }
        .workspace { min-width:0; display:grid; gap:22px; }
        .page-heading { display:flex; justify-content:space-between; align-items:flex-start; gap:16px; flex-wrap:wrap; }
        .page-heading h1 { margin:2px 0 6px; font-size:clamp(1.5rem,3vw,2rem); letter-spacing:-.04em; }
        .eyebrow { color:var(--accent); font-size:.75rem; font-weight:800; letter-spacing:.12em; text-transform:uppercase; }
        .muted { color:var(--muted); }
        .panel { background:#fff; border:1px solid var(--line); border-radius:16px; padding:20px; min-width:0; box-shadow:0 3px 14px rgba(15,23,42,.025); }
        .panel h2 { margin:0 0 14px; font-size:1.05rem; }
        .kpis { display:grid; grid-template-columns:repeat(4,minmax(0,1fr)); gap:14px; }
        .kpi-label { color:var(--muted); font-size:.83rem; font-weight:650; }
        .kpi-value { font-size:clamp(1.25rem,2.4vw,1.8rem); font-weight:800; letter-spacing:-.04em; margin:12px 0 6px; overflow-wrap:anywhere; }
        .kpi-foot { font-size:.78rem; color:var(--muted); }
        .content-grid { display:grid; grid-template-columns:minmax(0,1.3fr) minmax(280px,.7fr); gap:18px; }
        .table-wrap { overflow-x:auto; width:100%; }
        table { border-collapse:collapse; width:100%; min-width:600px; font-size:.88rem; }
        th,td { padding:12px 10px; border-bottom:1px solid #edf0f5; text-align:left; white-space:nowrap; }
        th { color:#64748b; font-size:.75rem; text-transform:uppercase; letter-spacing:.06em; background:#fafbfe; }
        tbody tr:hover { background:#fafbff; }
        .filters { display:flex; gap:10px; flex-wrap:wrap; margin:14px 0; }
        .filters input,.filters select { min-width:150px; flex:1; }
        .pill { display:inline-flex; padding:5px 9px; border-radius:999px; background:#eef2ff; color:#3730a3; font-size:.75rem; font-weight:750; }
        .alerts { display:grid; gap:9px; }
        .alert { border-radius:10px; padding:12px 13px; background:#fff7ed; color:#9a3412; font-size:.88rem; line-height:1.45; }
        .alert.info { background:#eff6ff; color:#1d4ed8; }
        .alert.success { background:#ecfdf5; color:#065f46; }
        .chart { width:100%; min-height:180px; }
        .chart svg { display:block; width:100%; height:auto; }
        .legend { display:flex; gap:14px; flex-wrap:wrap; color:var(--muted); font-size:.8rem; margin-top:8px; }
        .legend span:before { content:""; display:inline-block; width:9px; height:9px; border-radius:3px; background:#3659e3; margin-right:6px; }
        .legend .loss:before { background:#d97706; }
        .loading-line { height:10px; border-radius:6px; background:linear-gradient(90deg,#edf0f5,#f8fafc,#edf0f5); background-size:200% 100%; animation:shimmer 1.2s infinite; }
        @keyframes shimmer { to { background-position:-200% 0; } }
        .empty { padding:20px 10px; color:var(--muted); text-align:center; }
        .error-banner { padding:13px 15px; border:1px solid #fecaca; background:#fff1f2; color:#9f1239; border-radius:12px; }
        .refresh { background:#eef2ff; color:#273eaf; font-weight:700; }
        .table-action { background:#eef2ff; color:#273eaf; font-size:.8rem; font-weight:700; padding:7px 10px; white-space:nowrap; }
        .position-row { cursor:pointer; }
        .position-row:hover,.position-row.selected { background:#eef2ff; }
        .lot-row.selected { background:#fffbeb; }
        dialog { width:min(560px,calc(100% - 32px)); max-height:calc(100% - 32px); border:1px solid var(--line); border-radius:12px; padding:0; color:var(--ink); box-shadow:0 24px 70px rgba(15,23,42,.25); }
        dialog::backdrop { background:rgba(15,23,42,.5); }
        .dialog-content { padding:22px; }
        .dialog-content h2 { margin:0 0 16px; }
        .dialog-fields { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:13px; }
        .dialog-fields label { display:grid; gap:6px; font-size:.9rem; }
        .dialog-fields input,.dialog-fields select,.dialog-fields textarea { min-width:0; width:100%; padding:10px; border:1px solid var(--line); border-radius:7px; font:inherit; }
        .dialog-fields .wide { grid-column:1/-1; }
        .dialog-actions { display:flex; justify-content:flex-end; gap:9px; margin-top:18px; }
        .dialog-error { color:#9f1239; margin-top:12px; }
        .history-dialog { width:min(980px,calc(100% - 32px)); }
        .history-heading { display:flex; align-items:flex-start; justify-content:space-between; gap:16px; }
        .history-heading h2 { margin-bottom:4px; }
        .history-table-wrap { overflow-x:auto; margin-top:16px; }
        .history-table { min-width:850px; }
        .history-empty { padding:24px; color:var(--muted); text-align:center; }
        .lot-actions { display:flex; gap:6px; }
        .lot-actions .table-action { padding:7px 8px; }
        .table-action.secondary { background:#ecfdf5; color:#166534; }
        #calculators { scroll-margin-top:90px; }
        #calculators .calculator-shell { background:#fff; border:1px solid var(--line); border-radius:16px; padding:22px; }
        #calculators .status { background:#eff6ff; color:#1d4ed8; }
        #calculators .result { border:1px solid var(--line); }
        #calculators .tabs { margin:18px 0; }
        @media(max-width:1050px) { .app-shell { grid-template-columns:190px minmax(0,1fr); gap:16px; padding:0 16px; } .kpis { grid-template-columns:repeat(2,minmax(0,1fr)); } .content-grid { grid-template-columns:1fr; } }
        @media(max-width:700px) { .topbar { position:static; padding:14px 16px; } .app-shell { display:block; margin:14px auto; padding:0 12px; } .sidebar { position:static; padding:8px; margin-bottom:14px; } .side-label { display:none; } .side-nav { display:flex; overflow-x:auto; gap:4px; } .side-nav a { flex:0 0 auto; padding:10px; font-size:.82rem; } .workspace { gap:14px; } .panel { padding:15px; } .kpis { gap:9px; } .kpi-value { font-size:1.25rem; } #calculators .calculator-shell { padding:15px; } }

    </style>
</head>
<body>
    <header class="topbar">
        <div class="brand">Registro de Operações com Opções</div>
        <div class="actions">
            <span>Olá, <?php echo htmlspecialchars((string) ($user['username'] ?? 'Usuário'), ENT_QUOTES, 'UTF-8'); ?></span>
            <a href="/registros" style="color:white;text-decoration:none">Registros</a>
            <?php if (($user['profile'] ?? 'USER') === 'ADMIN'): ?>
                <a href="/admin/users" style="color:white;text-decoration:none">Gerenciar usuários</a>
            <?php endif; ?>
            <a href="/change-password" style="color:white;text-decoration:none">Alterar senha</a>
            <form class="logout-form" method="post" action="/logout">
                <input type="hidden" name="_csrf_token" value="<?php echo htmlspecialchars((string) App\Support\SessionManager::csrfToken(), ENT_QUOTES, 'UTF-8'); ?>">
                <button type="submit">Sair</button>
            </form>
        </div>
    </header>
    <div class="app-shell">
    <aside class="sidebar" aria-label="Navegação principal">
        <div class="side-label">Workspace</div>
        <nav class="side-nav">
            <a href="#overview" aria-current="page">▦ Visão geral</a><a href="#positions">▤ Posições por contrato</a><a href="#strategy-positions">▧ Posições por estratégia</a><a href="#lots">≡ Lotes em aberto</a><a href="#calculators">∑ Calculadoras</a><a href="/registros">▣ Registros</a>
        </nav>
    </aside>
    <main class="workspace">
        <section id="overview">
            <div class="page-heading"><div><div class="eyebrow">Painel de controle</div><h1>Visão geral da carteira</h1><p class="muted">Resumo calculado a partir dos registros vinculados à sua conta.</p></div><button class="refresh" id="refresh-dashboard" type="button">Atualizar dados</button></div>
            <div id="dashboard-error" class="error-banner" role="alert" hidden></div><div id="dashboard-alerts" class="alerts" aria-live="polite" style="margin:16px 0"></div>
            <div class="kpis">
                <article class="panel"><div class="kpi-label">Contratos com posição</div><div class="kpi-value" id="kpi-positions"><div class="loading-line"></div></div><div class="kpi-foot">Contratos com lotes ainda abertos</div></article>
                <article class="panel"><div class="kpi-label">Resultado realizado</div><div class="kpi-value" id="kpi-realized"><div class="loading-line"></div></div><div class="kpi-foot">Alocações de encerramento registradas</div></article>
                <article class="panel"><div class="kpi-label">Resultado não realizado</div><div class="kpi-value" id="kpi-unrealized"><div class="loading-line"></div></div><div class="kpi-foot" id="kpi-unrealized-foot">Depende de cotações registradas</div></article>
                <article class="panel"><div class="kpi-label">Fluxo de caixa acumulado</div><div class="kpi-value" id="kpi-cash"><div class="loading-line"></div></div><div class="kpi-foot">Fluxo bruto menos custos; não equivale a lucro</div></article>
            </div>
        </section>
        <section class="content-grid"><article class="panel"><h2>Resultados por contrato</h2><p class="muted" style="margin-top:-6px">Valores da API de posições; não representa histórico diário.</p><div id="results-chart" class="chart"><div class="loading-line"></div></div><div class="legend"><span>Realizado</span><span class="loss">Não realizado (quando cotado)</span></div></article><article class="panel"><h2>Alertas e qualidade dos dados</h2><div id="data-alerts" class="alerts"><div class="loading-line"></div></div></article></section>
        <section id="positions" class="panel"><h2>Posições por contrato</h2><p class="muted">Selecione um contrato para localizar os lotes correspondentes; os dados desta tabela são agregados.</p>
            <div class="filters"><input id="position-search" type="search" placeholder="Filtrar por código do contrato" aria-label="Filtrar contratos"><select id="position-state" aria-label="Filtrar estado da posição"><option value="all">Todas as posições</option><option value="open">Com posição aberta</option><option value="closed">Sem posição aberta</option><option value="unc quoted">Sem cotação</option></select></div>
            <div class="table-wrap"><table><thead><tr><th>Contrato</th><th>Qtd. líquida</th><th>Fluxo bruto</th><th>Custos</th><th>Realizado</th><th>Não realizado</th><th>Cotação</th><th>Ações</th></tr></thead><tbody id="positions-body"><tr><td colspan="8"><div class="loading-line"></div></td></tr></tbody></table></div>
        </section>
        <section id="strategy-positions" class="panel"><h2>Posições por estratégia</h2><p class="muted">Resumo agregado das operações vinculadas a cada estratégia. O resultado não realizado só aparece quando todos os contratos em aberto daquela estratégia têm cotação.</p>
            <div class="filters"><input id="strategy-search" type="search" placeholder="Filtrar por nome da estratégia" aria-label="Filtrar estratégias"><select id="strategy-state" aria-label="Filtrar estratégias"><option value="all">Todas as estratégias</option><option value="open">Com posição aberta</option><option value="closed">Sem posição aberta</option></select></div>
            <div class="table-wrap"><table><thead><tr><th>Estratégia</th><th>Pernas cadastradas</th><th>Contratos abertos</th><th>Fluxo líquido</th><th>Resultado realizado</th><th>Resultado não realizado</th><th>Resultado total informado</th><th>Status</th></tr></thead><tbody id="strategy-positions-body"><tr><td colspan="8"><div class="loading-line"></div></td></tr></tbody></table></div>
        </section>
        <section id="lots" class="panel"><h2>Lotes em aberto</h2><p class="muted">Aumente ou desmonte cada lote. A desmontagem pode ser parcial ou integral.</p><div class="filters"><input id="lot-search" type="search" placeholder="Filtrar por contrato ou lado" aria-label="Filtrar lotes"></div><div class="table-wrap"><table><thead><tr><th>Contrato</th><th>Lado</th><th>Qtd. aberta</th><th>Preço de entrada</th><th>Data</th><th>Tipo</th><th>Ações</th></tr></thead><tbody id="lots-body"><tr><td colspan="7"><div class="loading-line"></div></td></tr></tbody></table></div></section>
        <dialog id="operation-editor" aria-labelledby="operation-editor-title">
            <form id="operation-edit-form" class="dialog-content">
                <h2 id="operation-editor-title">Editar operação</h2>
                <input id="operation-edit-id" type="hidden">
                <input name="_csrf_token" type="hidden" value="<?php echo htmlspecialchars((string) App\Support\SessionManager::csrfToken(), ENT_QUOTES, 'UTF-8'); ?>">
                <div class="dialog-fields">
                    <label>Lado<select name="side" required><option value="BUY">Compra</option><option value="SELL">Venda</option></select></label>
                    <label>Quantidade<input name="quantity" type="number" min="1" step="1" required></label>
                    <label>Preço unitário<input name="unit_price" type="number" min="0" step="any" required></label>
                    <label>Custos<input name="costs" type="number" min="0" step="any" required></label>
                    <label>Data e hora<input name="executed_at" type="datetime-local" required></label>
                    <label>Tipo<select name="operation_kind"><option value="TRADE">Trade</option><option value="OPEN">Abertura</option><option value="CLOSE">Fechamento</option><option value="PARTIAL_CLOSE">Fechamento parcial</option><option value="REVERSAL">Inversão</option><option value="ADJUSTMENT">Ajuste</option></select></label>
                    <label class="wide">Estratégia<select name="strategy_id" id="operation-edit-strategy"><option value="">Sem estratégia</option></select></label>
                    <label class="wide">Observações<textarea name="notes" rows="3"></textarea></label>
                </div>
                <div id="operation-edit-error" class="dialog-error" role="alert" hidden></div>
                <div class="dialog-actions"><button class="refresh" type="button" id="operation-edit-cancel">Cancelar</button><button class="primary" type="submit">Salvar alterações</button></div>
            </form>
        </dialog>
        <dialog id="position-action-dialog" aria-labelledby="position-action-title">
            <form id="position-action-form" class="dialog-content">
                <h2 id="position-action-title">Aumentar posição</h2>
                <p id="position-action-hint" class="muted">A quantidade acima da posição atual fará a inversão, mantendo o excedente no lado oposto.</p>
                <input name="_csrf_token" type="hidden" value="<?php echo htmlspecialchars((string) App\Support\SessionManager::csrfToken(), ENT_QUOTES, 'UTF-8'); ?>">
                <div class="dialog-fields">
                    <label>Quantidade<input name="quantity" type="number" min="1" step="1" value="1" required></label>
                    <label>Preço unitário<input name="unit_price" type="number" min="0" step="any" required></label>
                    <label>Custos<input name="costs" type="number" min="0" step="any" value="0" required></label>
                    <label>Data e hora<input name="executed_at" type="datetime-local" required></label>
                </div>
                <div id="position-action-error" class="dialog-error" role="alert" hidden></div>
                <div class="dialog-actions"><button class="refresh" type="button" id="position-action-cancel">Cancelar</button><button class="primary" type="submit">Confirmar</button></div>
            </form>
        </dialog>
        <dialog id="transaction-history-dialog" class="history-dialog" aria-labelledby="transaction-history-title">
            <div class="dialog-content">
                <div class="history-heading">
                    <div><h2 id="transaction-history-title">Histórico do contrato</h2><div id="transaction-history-contract" class="muted"></div></div>
                    <button class="refresh" type="button" id="transaction-history-close" aria-label="Fechar histórico">Fechar</button>
                </div>
                <div id="transaction-history-content" aria-live="polite"><div class="loading-line"></div></div>
            </div>
        </dialog>
        <section id="calculators"><div class="calculator-shell">
        <div class="status">Dashboard protegido</div>
        <h1>Calculadoras de opções</h1>
        <p>Resultados teóricos calculados apenas com os parâmetros informados. Não são cotações nem recomendação de investimento.</p>
        <div class="tabs" role="tablist" aria-label="Calculadoras">
            <button type="button" role="tab" id="tab-bs" aria-controls="panel-bs" aria-selected="true">Black-Scholes</button>
            <button type="button" role="tab" id="tab-reverse" aria-controls="panel-reverse" aria-selected="false">Strike reverso</button>
        </div>
        <section id="panel-bs" role="tabpanel" aria-labelledby="tab-bs">
            <h2>Preço teórico — Black-Scholes-Merton</h2>
            <form id="bs-form">
                <div class="fields">
                    <label>Tipo de opção
                        <select name="kind"><option value="call">Call</option><option value="put">Put</option></select>
                    </label>
                    <label>Preço do ativo (S)
                        <input name="spot" type="number" min="0.000001" step="any" value="100" required>
                    </label>
                    <label>Strike (K)
                        <input name="strike" type="number" min="0.000001" step="any" value="100" required>
                    </label>
                    <label>Tempo até vencimento (anos)
                        <input name="time" type="number" min="0" step="any" value="0.5" required>
                    </label>
                    <label>Volatilidade anual (%)
                        <input name="volatility" type="number" min="0" step="any" value="25" required>
                    </label>
                    <label>Taxa livre de risco anual (%)
                        <input name="rate" type="number" step="any" value="10" required>
                    </label>
                    <label>Dividend yield contínuo anual (%)
                        <input name="dividend" type="number" min="0" step="any" value="0" required>
                    </label>
                </div>
                <button class="primary" type="submit">Calcular preço teórico</button>
            </form>
            <div id="bs-result" class="result" role="status" aria-live="polite">Informe os parâmetros e calcule.</div>
            <p class="muted">Taxas e volatilidade são convertidas de percentual para decimal; tempo em anos. Modelo europeu com dividend yield contínuo.</p>
        </section>
        <section id="panel-reverse" role="tabpanel" aria-labelledby="tab-reverse" hidden>
            <h2>Strike reverso</h2>
            <form id="reverse-form">
                <div class="fields">
                    <label>Tipo de opção
                        <select name="kind"><option value="call">Call</option><option value="put">Put</option></select>
                    </label>
                    <label>Preço do ativo (S)
                        <input name="spot" type="number" min="0.000001" step="any" value="100" required>
                    </label>
                    <label>Preço-alvo da opção
                        <input name="target" type="number" min="0" step="any" value="8" required>
                    </label>
                    <label>Tempo até vencimento (anos)
                        <input name="time" type="number" min="0" step="any" value="0.5" required>
                    </label>
                    <label>Volatilidade anual (%)
                        <input name="volatility" type="number" min="0" step="any" value="25" required>
                    </label>
                    <label>Taxa livre de risco anual (%)
                        <input name="rate" type="number" step="any" value="10" required>
                    </label>
                    <label>Dividend yield contínuo anual (%)
                        <input name="dividend" type="number" min="0" step="any" value="0" required>
                    </label>
                    <label>Strikes listados (opcional)
                        <input name="listed" type="text" placeholder="Ex.: 90, 95, 100, 105, 110">
                    </label>
                </div>
                <button class="primary" type="submit">Encontrar strike teórico</button>
            </form>
            <div id="reverse-result" class="result" role="status" aria-live="polite">Informe o preço-alvo e os parâmetros.</div>
            <p class="muted">A grade de strikes é opcional e deve ser fornecida por você. Sem ela, o sistema não presume que um strike esteja listado ou disponível.</p>
        </section>
            </div>
        </section>
    </main>
</div>
<script>
(() => {
 'use strict';
 const $=id=>document.getElementById(id), state={positions:[],contracts:[],assets:[],strategies:[],strategyPositions:[],rollovers:[],selectedContract:null};
 const money=v=>v===null||v===undefined||!Number.isFinite(Number(v))?'—':new Intl.NumberFormat('pt-BR',{style:'currency',currency:'BRL'}).format(Number(v));
 const num=v=>new Intl.NumberFormat('pt-BR',{maximumFractionDigits:4}).format(Number(v)||0);
 const safe=v=>String(v??'').replace(/[&<>"']/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
 const date=v=>{if(!v)return'—';const d=new Date(String(v).replace(' ','T'));return Number.isNaN(d.getTime())?safe(v):d.toLocaleDateString('pt-BR')};
 async function get(path){const r=await fetch(path,{credentials:'same-origin',headers:{Accept:'application/json'}});let p;try{p=await r.json()}catch{throw new Error('Resposta inválida da API ('+path+').')}if(!r.ok||p.success===false)throw new Error(p.message||'Falha ao carregar '+path+'.');return p.data??[]}
 function contractName(id){const c=state.contracts.find(x=>Number(x.id)===Number(id));if(!c)return'Contrato #'+id;const a=state.assets.find(x=>Number(x.id)===Number(c.asset_id));return(c.code||('Contrato #'+id))+(a&&a.symbol?' · '+a.symbol:'')}
 const alertBox=(t,k='')=>'<div class="alert '+k+'">'+safe(t)+'</div>';
 function renderAlerts(){const a=[],open=state.positions.filter(p=>(p.open_lots||[]).length>0),missing=open.filter(p=>!p.latest_quote);if(missing.length)a.push(alertBox(missing.length+' contrato(s) com posição aberta sem cotação. Resultado não realizado indisponível.'));const today=new Date();today.setHours(0,0,0,0);const exp=state.contracts.filter(c=>String(c.status).toUpperCase()==='ACTIVE'&&c.expiration_date&&(new Date(c.expiration_date+'T00:00:00')-today)>=0&&(new Date(c.expiration_date+'T00:00:00')-today)<=7*86400000);if(exp.length)a.push(alertBox(exp.length+' contrato(s) ativo(s) vencem nos próximos 7 dias.'));const inactive=state.contracts.filter(c=>String(c.status).toUpperCase()!=='ACTIVE').length;if(inactive)a.push(alertBox(inactive+' cadastro(s) de contrato inativos.','info'));if(!state.positions.length)a.push(alertBox('Ainda não há posições derivadas de operações cadastradas.','info'));if(!a.length)a.push(alertBox('Nenhum alerta identificado nos dados carregados.','success'));$('dashboard-alerts').innerHTML=a.join('');$('data-alerts').innerHTML=a.join('')}
 function renderStrategyPositions(){const q=$('strategy-search').value.trim().toLowerCase(),f=$('strategy-state').value;const rows=state.strategyPositions.filter(s=>String(s.name||'').toLowerCase().includes(q)&&(f==='all'||f==='open'&&Number(s.open_contracts)>0||f==='closed'&&Number(s.open_contracts)===0));$('strategy-positions-body').innerHTML=rows.length?rows.map(s=>{const total=s.unrealized_result===null||s.unrealized_result===undefined?null:Number(s.realized_result||0)+Number(s.unrealized_result||0);const status=Number(s.open_contracts)>0?'Aberta':'Sem posição aberta';const legNames=(s.legs||[]).map(l=>{const side=String(l.side||'').toUpperCase()==='SELL'?'Venda':'Compra';const kind=String(l.option_type||l.optionType||'OPÇÃO').toUpperCase();const strike=l.strike_price??l.strikePrice??l.strike;return side+' '+kind+(strike!==undefined?' '+num(strike):'')}).join(' · ');return '<tr><td><strong>'+safe(s.name)+'</strong><br><small class="muted">'+safe(legNames||s.description||'Sem detalhes de pernas')+'</small></td><td>'+num(s.leg_count||0)+'</td><td>'+num(s.open_contracts||0)+'<br><small class="muted">Qtd. aberta: '+num(s.open_quantity||0)+'</small></td><td>'+money(s.net_cash_flow)+'</td><td>'+money(s.realized_result)+'</td><td>'+(s.unrealized_result===null||s.unrealized_result===undefined?'<span class="pill">Sem cotação completa</span>':money(s.unrealized_result))+'</td><td>'+(total===null?'<span class="muted">Parcial</span>':money(total))+'</td><td><span class="pill">'+safe(status)+'</span></td></tr>'}).join(''):'<tr><td colspan="8" class="empty">Nenhuma estratégia corresponde ao filtro. Cadastre uma estratégia e vincule as operações para ver seu resumo.</td></tr>'}
 function renderPositions(){const q=$('position-search').value.trim().toLowerCase(),f=$('position-state').value,rows=state.positions.filter(p=>{const n=contractName(p.option_contract_id).toLowerCase(),o=(p.open_lots||[]).length>0,quoted=!!p.latest_quote;return n.includes(q)&&(f==='all'||f==='open'&&o||f==='closed'&&!o||f==='unc quoted'&&!quoted)});$('positions-body').innerHTML=rows.length?rows.map(p=>'<tr class="position-row'+(Number(state.selectedContract)===Number(p.option_contract_id)?' selected':'')+'"><td>'+safe(contractName(p.option_contract_id))+'</td><td>'+num(p.open_quantity)+'</td><td>'+money(p.gross_cash_flow)+'</td><td>'+money(p.costs)+'</td><td>'+money(p.realized_result)+'</td><td>'+money(p.unrealized_result)+'</td><td>'+(p.latest_quote?money(p.latest_quote.quote_price)+'<br><small>'+date(p.latest_quote.quoted_at)+'</small>':'<span class="pill">Sem cotação</span>')+'</td><td><div class="lot-actions"><button class="table-action" type="button" data-select-contract="'+Number(p.option_contract_id)+'">Selecionar lotes</button><button class="table-action secondary" type="button" data-contract-history="'+Number(p.option_contract_id)+'">Histórico</button></div></td></tr>').join(''):'<tr><td colspan="8" class="empty">Nenhuma posição corresponde ao filtro.</td></tr>'}
 async function openContractHistory(contractId){const dialog=$('transaction-history-dialog'),content=$('transaction-history-content');$('transaction-history-contract').textContent=contractName(contractId);content.innerHTML='<div class="loading-line"></div>';dialog.showModal();try{const rows=await get('/api/contracts/'+encodeURIComponent(contractId)+'/transactions');content.innerHTML=rows.length?'<div class="history-table-wrap"><table class="history-table"><thead><tr><th>Data</th><th>Tipo</th><th>Lado</th><th>Quantidade</th><th>Preço unitário</th><th>Fluxo bruto</th><th>Custos</th><th>Resultado realizado</th><th>Observações</th></tr></thead><tbody>'+rows.map(row=>'<tr><td>'+safe(row.executed_at)+'</td><td>'+safe(row.operation_kind||'TRADE')+'</td><td>'+safe(row.side)+'</td><td>'+num(row.quantity)+'</td><td>'+money(row.unit_price)+'</td><td>'+money(row.gross_cash_flow)+'</td><td>'+money(row.costs)+'</td><td>'+money(row.realized_result)+'</td><td>'+safe(row.notes||'—')+'</td></tr>').join('')+'</tbody></table></div>':'<div class="history-empty">Nenhuma transação registrada para este contrato.</div>'}catch(error){content.innerHTML='<div class="error-banner" role="alert">'+safe(error.message)+'</div>'}}
 function renderLots(){const q=$('lot-search').value.trim().toLowerCase(),lots=[];state.positions.forEach(p=>(p.open_lots||[]).forEach(l=>lots.push({...l,contract_id:p.option_contract_id,net_quantity:p.open_quantity})));const rows=lots.filter(l=>(state.selectedContract===null||Number(l.contract_id)===Number(state.selectedContract))&&(contractName(l.contract_id)+' '+l.side).toLowerCase().includes(q));$('lots-body').innerHTML=rows.length?rows.map(l=>{const canIncrease=l.net_quantity===0||(l.net_quantity>0&&l.side==='BUY')||(l.net_quantity<0&&l.side==='SELL'),canDismantle=(l.net_quantity>0&&l.side==='BUY')||(l.net_quantity<0&&l.side==='SELL');return'<tr class="lot-row'+(Number(state.selectedContract)===Number(l.contract_id)?' selected':'')+'"><td>'+safe(contractName(l.contract_id))+'</td><td><span class="pill">'+safe(l.side)+'</span></td><td>'+num(l.open_quantity)+'</td><td>'+money(l.unit_price)+'</td><td>'+date(l.executed_at)+'</td><td>'+safe(l.operation_kind||'TRADE')+'</td><td><div class="lot-actions"><button class="table-action" type="button" data-edit-operation="'+Number(l.id)+'">Editar</button><button class="table-action secondary" type="button" data-position-action="increase" data-operation-id="'+Number(l.id)+'"'+(canIncrease?'':' disabled title="O lado do lote não aumenta a posição líquida atual."')+'>Aumentar</button><button class="table-action" type="button" data-position-action="dismantle" data-operation-id="'+Number(l.id)+'"'+(canDismantle?'':' disabled title="O lado do lote não corresponde à posição líquida atual."')+'>Desmontar</button></div></td></tr>'}).join(''):'<tr><td colspan="7" class="empty">Nenhum lote em aberto corresponde ao filtro.</td></tr>'}
 function selectPosition(contractId){state.selectedContract=contractId;$('lot-search').value='';renderPositions();renderLots();$('lots').scrollIntoView({behavior:'smooth',block:'start'})}
 function openOperationEditor(operationId){const operation=state.positions.flatMap(p=>p.open_lots||[]).find(l=>Number(l.id)===Number(operationId));if(!operation)return;const form=$('operation-edit-form');$('operation-edit-id').value=String(operationId);form.elements.namedItem('side').value=operation.side;form.elements.namedItem('quantity').value=String(operation.quantity);form.elements.namedItem('unit_price').value=String(operation.unit_price);form.elements.namedItem('costs').value=String(operation.costs||0);form.elements.namedItem('executed_at').value=String(operation.executed_at||'').replace(' ','T').slice(0,16);form.elements.namedItem('operation_kind').value=operation.operation_kind||'TRADE';form.elements.namedItem('notes').value=operation.notes||'';const strategies=$('operation-edit-strategy');strategies.innerHTML='<option value="">Sem estratégia</option>'+state.strategies.map(s=>'<option value="'+Number(s.id)+'">'+safe(s.name)+'</option>').join('');strategies.value=operation.strategy_id?String(operation.strategy_id):'';$('operation-edit-error').hidden=true;$('operation-editor').showModal()}
 function openPositionAction(operationId,action){const position=state.positions.find(p=>(p.open_lots||[]).some(l=>Number(l.id)===Number(operationId)));const lot=position?.open_lots.find(l=>Number(l.id)===Number(operationId));if(!position||!lot)return;state.positionAction={position,lot,action};const form=$('position-action-form'),quantity=form.elements.namedItem('quantity'),title=$('position-action-title'),hint=$('position-action-hint');title.textContent=action==='increase'?'Aumentar posição':'Desmontar posição';hint.hidden=action!=='dismantle';quantity.removeAttribute('max');quantity.value=action==='dismantle'?String(Math.abs(Number(position.open_quantity))):'1';form.elements.namedItem('unit_price').value=String(lot.unit_price);form.elements.namedItem('costs').value='0';form.elements.namedItem('executed_at').value=new Date(Date.now()-new Date().getTimezoneOffset()*60000).toISOString().slice(0,16);$('position-action-error').hidden=true;$('position-action-dialog').showModal()}
 function bindPositionActions(){const dialog=$('position-action-dialog'),form=$('position-action-form');$('lots-body').addEventListener('click',event=>{const button=event.target.closest('[data-position-action]');if(button&&!button.disabled)openPositionAction(button.dataset.operationId,button.dataset.positionAction)});$('position-action-cancel').addEventListener('click',()=>dialog.close());form.addEventListener('submit',async event=>{event.preventDefault();const error=$('position-action-error'),button=form.querySelector('[type="submit"]'),selection=state.positionAction;if(!selection)return;button.disabled=true;error.hidden=true;try{const formData=new FormData(form),quantity=Number(formData.get('quantity')),netQuantity=Math.abs(Number(selection.position.open_quantity)),isDismantle=selection.action==='dismantle',side=isDismantle?(selection.lot.side==='BUY'?'SELL':'BUY'):selection.lot.side;if(!Number.isInteger(quantity)||quantity<1)throw new Error('Informe uma quantidade inteira positiva.');const operationKind=isDismantle?(quantity<netQuantity?'PARTIAL_CLOSE':quantity===netQuantity?'CLOSE':'REVERSAL'):'OPEN',data={_csrf_token:formData.get('_csrf_token'),option_contract_id:selection.position.option_contract_id,side,quantity,unit_price:formData.get('unit_price'),costs:formData.get('costs'),executed_at:String(formData.get('executed_at')).replace('T',' ')+':00',operation_kind:operationKind,notes:isDismantle?(operationKind==='REVERSAL'?'Desmontagem com inversão pelo painel':'Desmontagem iniciada pelo painel'):'Aumento iniciado pelo painel'};if(selection.lot.strategy_id)data.strategy_id=selection.lot.strategy_id;if(isDismantle)data.reference_operation_id=selection.lot.id;const response=await fetch('/api/operations',{method:'POST',credentials:'same-origin',headers:{Accept:'application/json','Content-Type':'application/json'},body:JSON.stringify(data)}),payload=await response.json();if(!response.ok||payload.success===false)throw new Error(payload.message||Object.values(payload.errors||{}).join(' ')||'Não foi possível registrar a operação.');dialog.close();await load()}catch(err){error.textContent=err.message;error.hidden=false}finally{button.disabled=false}})}
 function bindOperationEditor(){const dialog=$('operation-editor'),form=$('operation-edit-form');$('lots-body').addEventListener('click',event=>{const button=event.target.closest('[data-edit-operation]');if(button)openOperationEditor(button.dataset.editOperation)});$('positions-body').addEventListener('click',event=>{const button=event.target.closest('[data-select-contract]');if(button)selectPosition(Number(button.dataset.selectContract))});$('operation-edit-cancel').addEventListener('click',()=>dialog.close());form.addEventListener('submit',async event=>{event.preventDefault();const error=$('operation-edit-error'),button=form.querySelector('[type="submit"]');error.hidden=true;button.disabled=true;try{const data=Object.fromEntries(new FormData(form).entries());const operationId=$('operation-edit-id').value;const response=await fetch('/api/operations/'+encodeURIComponent(operationId),{method:'PUT',credentials:'same-origin',headers:{Accept:'application/json','Content-Type':'application/json'},body:JSON.stringify(data)});const payload=await response.json();if(!response.ok||payload.success===false)throw new Error(payload.message||Object.values(payload.errors||{}).join(' ')||'Não foi possível salvar as alterações.');dialog.close();await load()}catch(err){error.textContent=err.message;error.hidden=false}finally{button.disabled=false}})}
 function renderChart(){const rows=state.positions.filter(p=>Number(p.realized_result)!==0||p.unrealized_result!==null).slice(0,8);if(!rows.length){$('results-chart').innerHTML='<div class="empty">Ainda não há resultados para comparar.</div>';return}const max=Math.max(1,...rows.flatMap(p=>[Math.abs(Number(p.realized_result)||0),Math.abs(Number(p.unrealized_result)||0)])),w=600,h=rows.length*43+10,half=220,center=300;let svg='<svg viewBox="0 0 '+w+' '+h+'" role="img" aria-label="Gráfico de barras divergentes de resultados positivos e negativos por contrato"><line x1="'+center+'" y1="0" x2="'+center+'" y2="'+h+'" stroke="#cbd5e1" stroke-dasharray="3 3"/>';rows.forEach((p,i)=>{const y=i*43+5,r=Number(p.realized_result)||0,u=p.unrealized_result===null?null:Number(p.unrealized_result)||0;svg+='<text x="0" y="'+(y+11)+'" font-size="11" fill="#475569">'+safe(contractName(p.option_contract_id).slice(0,27))+'</text>';const rw=Math.abs(r)/max*half,rx=r<0?center-rw:center;svg+='<rect x="'+rx+'" y="'+(y+15)+'" width="'+rw+'" height="7" rx="3" fill="'+(r<0?'#d97706':'#3659e3')+'"><title>Realizado: '+safe(money(r))+'</title></rect>';if(u!==null){const uw=Math.abs(u)/max*half,ux=u<0?center-uw:center;svg+='<rect x="'+ux+'" y="'+(y+25)+'" width="'+uw+'" height="7" rx="3" fill="'+(u<0?'#d97706':'#0f9f6e')+'"><title>Não realizado: '+safe(money(u))+'</title></rect>'}});$('results-chart').innerHTML=svg+'</svg>'}
 function render(){const open=state.positions.filter(p=>(p.open_lots||[]).length>0),realized=state.positions.reduce((s,p)=>s+(Number(p.realized_result)||0),0),cash=state.positions.reduce((s,p)=>s+(Number(p.gross_cash_flow)||0)-(Number(p.costs)||0),0),allQuoted=open.every(p=>p.unrealized_result!==null&&p.unrealized_result!==undefined),unrealized=allQuoted?open.reduce((s,p)=>s+(Number(p.unrealized_result)||0),0):null;$('kpi-positions').textContent=String(open.length);$('kpi-realized').textContent=money(realized);$('kpi-unrealized').textContent=unrealized===null?'Indisponível':money(unrealized);$('kpi-unrealized-foot').textContent=unrealized===null?'Registre cotações para todos os contratos em aberto':'Todos os contratos abertos têm cotação registrada';$('kpi-cash').textContent=money(cash);renderAlerts();renderPositions();renderStrategyPositions();renderLots();renderChart()}
 async function load(){const e=$('dashboard-error');e.hidden=true;$('positions-body').innerHTML='<tr><td colspan="8"><div class="loading-line"></div></td></tr>';$('lots-body').innerHTML='<tr><td colspan="7"><div class="loading-line"></div></td></tr>';try{const results=await Promise.all([get('/api/positions'),get('/api/option-contracts'),get('/api/assets'),get('/api/strategies'),get('/api/strategy-positions')]);[state.positions,state.contracts,state.assets,state.strategies,state.strategyPositions]=results;render()}catch(err){e.textContent='Não foi possível carregar dados reais: '+err.message+' Verifique sua sessão e tente atualizar.';e.hidden=false;$('dashboard-alerts').innerHTML=alertBox(err.message);$('data-alerts').innerHTML=alertBox('Dados incompletos: indicadores não atualizados.','info');$('positions-body').innerHTML='<tr><td colspan="8" class="empty">Erro ao carregar posições.</td></tr>';$('strategy-positions-body').innerHTML='<tr><td colspan="8" class="empty">Erro ao carregar estratégias.</td></tr>';$('lots-body').innerHTML='<tr><td colspan="7" class="empty">Erro ao carregar lotes.</td></tr>';['kpi-positions','kpi-realized','kpi-unrealized','kpi-cash'].forEach(id=>$(id).textContent='—');$('results-chart').innerHTML='<div class="empty">Gráfico indisponível enquanto a API falhar.</div>'}}
 bindOperationEditor();bindPositionActions();$('positions-body').addEventListener('click',event=>{const button=event.target.closest('[data-contract-history]');if(button)openContractHistory(button.dataset.contractHistory)});$('transaction-history-close').addEventListener('click',()=>$('transaction-history-dialog').close());$('refresh-dashboard').addEventListener('click',load);$('position-search').addEventListener('input',renderPositions);$('strategy-search').addEventListener('input',renderStrategyPositions);$('strategy-state').addEventListener('change',renderStrategyPositions);$('position-state').addEventListener('change',renderPositions);$('lot-search').addEventListener('input',renderLots);document.querySelectorAll('.side-nav a').forEach(a=>a.addEventListener('click',()=>{document.querySelectorAll('.side-nav a').forEach(n=>n.removeAttribute('aria-current'));a.setAttribute('aria-current','page')}));load();
})();
</script>

<script>
(() => {
    'use strict';
    const $ = (id) => document.getElementById(id);
    const format = (value) => new Intl.NumberFormat('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 8 }).format(value);
    function cdf(x) {
        const a = Math.abs(x), t = 1 / (1 + 0.2316419 * a);
        const density = Math.exp(-a * a / 2) / Math.sqrt(2 * Math.PI);
        const p = 1 - density * (0.319381530*t - 0.356563782*t*t + 1.781477937*t**3 - 1.821255978*t**4 + 1.330274429*t**5);
        return x >= 0 ? p : 1 - p;
    }
    function price(p, strike) {
        const {kind, spot, time, volatility, rate, dividend} = p;
        if (![spot,strike,time,volatility,rate,dividend].every(Number.isFinite) || spot <= 0 || strike <= 0 || time < 0 || volatility < 0 || dividend < 0) throw new Error('Use valores numéricos válidos; S e K devem ser positivos, e tempo, volatilidade e dividend yield não podem ser negativos.');
        if (time === 0) return kind === 'call' ? Math.max(spot-strike,0) : Math.max(strike-spot,0);
        if (volatility === 0) {
            const forwardSpot = spot * Math.exp(-dividend*time), discountedStrike = strike*Math.exp(-rate*time);
            return Math.max(kind === 'call' ? forwardSpot-discountedStrike : discountedStrike-forwardSpot, 0);
        }
        const sigmaT = volatility*Math.sqrt(time);
        const d1 = (Math.log(spot/strike)+(rate-dividend+volatility*volatility/2)*time)/sigmaT;
        const d2 = d1-sigmaT;
        return kind === 'call'
            ? spot*Math.exp(-dividend*time)*cdf(d1)-strike*Math.exp(-rate*time)*cdf(d2)
            : strike*Math.exp(-rate*time)*cdf(-d2)-spot*Math.exp(-dividend*time)*cdf(-d1);
    }
    function params(form) {
        const d = new FormData(form);
        const p = {kind:d.get('kind'), spot:Number(d.get('spot')), time:Number(d.get('time')), volatility:Number(d.get('volatility'))/100, rate:Number(d.get('rate'))/100, dividend:Number(d.get('dividend'))/100};
        if (![p.spot,p.time,p.volatility,p.rate,p.dividend].every(Number.isFinite)) throw new Error('Preencha todos os campos com números válidos.');
        if (p.spot <= 0 || p.time < 0 || p.volatility < 0 || p.dividend < 0) throw new Error('Preço do ativo deve ser positivo; tempo, volatilidade e dividend yield não podem ser negativos.');
        return {p,d};
    }
    function show(node, message, error=false) { node.textContent=message; node.classList.toggle('error',error); }
    $('bs-form').addEventListener('submit', (event) => {
        event.preventDefault();
        const out=$('bs-result');
        try { const {p,d}=params(event.currentTarget); const k=Number(d.get('strike')); const result=price(p,k); if(!Number.isFinite(k)||k<=0) throw new Error('Strike deve ser positivo.'); show(out, 'Preço teórico por opção: '+format(result)+'. Modelo Black-Scholes-Merton, sem custos, spread ou exercício antecipado.'); }
        catch(e) { show(out,e.message,true); }
    });
    $('reverse-form').addEventListener('submit', (event) => {
        event.preventDefault();
        const out=$('reverse-result');
        try {
            const {p,d}=params(event.currentTarget), target=Number(d.get('target'));
            if(!Number.isFinite(target)||target<0) throw new Error('O preço-alvo deve ser um número não negativo.');
            if(p.time===0 || p.volatility===0) throw new Error('Com vencimento imediato ou volatilidade zero, o payoff pode ter trechos sem solução única. Informe tempo e volatilidade positivos para usar a busca numérica.');
            const valueAt=(k)=>price(p,k)-target;
            let lo=Math.max(p.spot*1e-8,1e-8), hi=p.spot*2, flo=valueAt(lo), fhi=valueAt(hi), expansions=0;
            while(flo*fhi>0 && expansions<80) { hi*=2; fhi=valueAt(hi); expansions++; }
            if(flo===0) hi=lo;
            else if(fhi===0) lo=hi;
            else if(flo*fhi>0) throw new Error('Não foi possível encontrar um intervalo que contenha uma solução para esse preço-alvo. Verifique os parâmetros e os limites teóricos da opção.');
            else {
                for(let i=0;i<200;i++) { const mid=(lo+hi)/2, fm=valueAt(mid); if(Math.abs(fm)<1e-10 || (hi-lo)<1e-10*Math.max(1,mid)) {lo=hi=mid;break;} if(flo*fm<=0){hi=mid;fhi=fm;}else{lo=mid;flo=fm;} }
            }
            const theoretical=(lo+hi)/2;
            let message='Strike teórico aproximado: '+format(theoretical)+'. Preço calculado nesse strike: '+format(price(p,theoretical))+'.';
            const raw=d.get('listed').trim();
            if(raw) {
                const strikes=raw.split(/[,;\s]+/).filter(Boolean).map(Number);
                if(!strikes.length||strikes.some(k=>!Number.isFinite(k)||k<=0)) throw new Error('A lista de strikes deve conter apenas números positivos separados por vírgula, ponto e vírgula ou espaço.');
                const listed=[...new Set(strikes)].sort((a,b)=>a-b);
                const nearest=listed.reduce((best,k)=>Math.abs(k-theoretical)<Math.abs(best-theoretical)?k:best,listed[0]);
                message+=' Strike listado mais próximo, dentre os valores informados: '+format(nearest)+'. Preço teórico nesse strike listado: '+format(price(p,nearest))+'. A proximidade não garante liquidez nem disponibilidade.';
            } else message+=' Nenhuma grade de strikes foi informada; portanto, não é possível identificar um strike listado.';
            show(out,message);
        } catch(e) { show(out,e.message,true); }
    });
    for (const [tab,panel] of [['tab-bs','panel-bs'],['tab-reverse','panel-reverse']]) $(tab).addEventListener('click',()=>{
        for(const [t,pan] of [['tab-bs','panel-bs'],['tab-reverse','panel-reverse']]) { $(t).setAttribute('aria-selected',String(t===tab)); $(pan).hidden=pan!==panel; }
    });
})();
</script>
</body>
</html>
