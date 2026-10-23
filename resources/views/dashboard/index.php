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
    </style>
</head>
<body>
    <header class="topbar">
        <div class="brand">Registro de Operações com Opções</div>
        <div class="actions">
            <span>Olá, <?php echo htmlspecialchars((string) ($user['username'] ?? 'Usuário'), ENT_QUOTES, 'UTF-8'); ?></span>
            <a href="/change-password" style="color:white;text-decoration:none">Alterar senha</a>
            <form class="logout-form" method="post" action="/logout">
                <input type="hidden" name="_csrf_token" value="<?php echo htmlspecialchars((string) App\Support\SessionManager::csrfToken(), ENT_QUOTES, 'UTF-8'); ?>">
                <button type="submit">Sair</button>
            </form>
        </div>
    </header>
    <main class="card">
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
    </main>
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
