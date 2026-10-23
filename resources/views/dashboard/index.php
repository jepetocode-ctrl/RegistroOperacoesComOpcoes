<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Dashboard | Registro de Operações com Opções</title>
    <style>
        body { margin: 0; font-family: Arial, sans-serif; background: #f8fafc; color: #0f172a; }
        .topbar { background: #0f172a; color: white; padding: 18px 24px; display: flex; justify-content: space-between; align-items: center; }
        .brand { font-weight: 700; }
        .actions { display: flex; align-items: center; gap: 12px; }
        .card { max-width: 1100px; margin: 32px auto; background: white; border-radius: 16px; box-shadow: 0 12px 30px rgba(15, 23, 42, 0.08); padding: 24px; }
        .logout-form { display: inline; }
        .logout-form button { background: #dc2626; color: white; border: none; padding: 10px 14px; border-radius: 8px; cursor: pointer; }
        .status { display: inline-block; background: #ecfdf5; color: #065f46; padding: 8px 12px; border-radius: 999px; font-weight: 700; margin-bottom: 16px; }
    </style>
</head>
<body>
    <div class="topbar">
        <div class="brand">Registro de Operações com Opções</div>
        <div class="actions">
            <span>Olá, <?php echo htmlspecialchars((string) ($user['username'] ?? 'Usuário')); ?></span>
            <a href="/change-password" style="color: white; text-decoration: none;">Alterar senha</a>
            <form class="logout-form" method="post" action="/logout">
                <input type="hidden" name="_csrf_token" value="<?php echo htmlspecialchars((string) 
                    App\Support\SessionManager::csrfToken()); ?>">
                <button type="submit">Sair</button>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="status">Dashboard protegido</div>
        <h1>Bem-vindo à plataforma</h1>
        <p>Você está autenticado e pode continuar para os módulos operacionais da aplicação.</p>
    </div>
</body>
</html>
