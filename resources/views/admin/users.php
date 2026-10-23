<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Usuários | Administração</title>
    <style>
        :root { color-scheme: light; }
        body { margin: 0; font-family: Arial, sans-serif; background: #f8fafc; color: #0f172a; }
        .topbar { background: #0f172a; color: white; padding: 18px 24px; display: flex; justify-content: space-between; align-items: center; gap: 16px; flex-wrap: wrap; }
        .brand { font-weight: 700; }
        .actions { display: flex; align-items: center; gap: 12px; flex-wrap: wrap; }
        a { color: #1d4ed8; text-decoration: none; }
        .shell { max-width: 1200px; margin: 24px auto; padding: 0 20px; }
        .panel { background: white; border: 1px solid #e2e8f0; border-radius: 16px; padding: 22px; box-shadow: 0 10px 25px rgba(15, 23, 42, 0.04); }
        .header { display: flex; justify-content: space-between; align-items: center; gap: 12px; flex-wrap: wrap; margin-bottom: 18px; }
        .header h1 { margin: 0; font-size: clamp(1.6rem, 2vw, 2.2rem); }
        .grid { display: grid; grid-template-columns: minmax(260px, 420px) minmax(0, 1fr); gap: 22px; }
        fieldset { border: 1px solid #e2e8f0; border-radius: 12px; padding: 16px; }
        legend { font-weight: 700; padding: 0 8px; }
        .fields { display: grid; gap: 14px; }
        label { display: flex; flex-direction: column; gap: 6px; font-size: 0.95rem; }
        input, select { padding: 10px 12px; border: 1px solid #cbd5e1; border-radius: 8px; font: inherit; }
        .checkboxes { display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 10px; }
        .checkboxes label { display: flex; flex-direction: row; align-items: center; gap: 8px; padding: 8px 10px; border: 1px solid #e2e8f0; border-radius: 8px; background: #f8fafc; }
        button { border: 0; border-radius: 8px; padding: 10px 14px; cursor: pointer; font: inherit; }
        .primary { background: #2563eb; color: white; }
        .secondary { background: #e2e8f0; color: #0f172a; }
        .notice { padding: 12px 14px; border-radius: 10px; margin-bottom: 18px; }
        .error { background: #fee2e2; color: #991b1b; border: 1px solid #fecaca; }
        .success { background: #dcfce7; color: #166534; border: 1px solid #bbf7d0; }
        table { width: 100%; border-collapse: collapse; min-width: 680px; }
        th, td { padding: 12px 10px; border-bottom: 1px solid #edf2f7; text-align: left; vertical-align: top; }
        th { font-size: 0.75rem; letter-spacing: 0.08em; text-transform: uppercase; color: #64748b; }
        .pill { display: inline-flex; padding: 5px 8px; border-radius: 999px; background: #e0f2fe; color: #075985; font-size: 0.75rem; font-weight: 700; }
        .form-row { display: flex; gap: 10px; flex-wrap: wrap; }
        .muted { color: #475569; }
        @media (max-width: 900px) {
            .grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
    <header class="topbar">
        <div class="brand">Registro de Operações com Opções</div>
        <div class="actions">
            <span>Olá, <?php echo htmlspecialchars((string) ($user['username'] ?? 'Administrador'), ENT_QUOTES, 'UTF-8'); ?></span>
            <a href="/dashboard" style="color:white;text-decoration:none;">Voltar para dashboard</a>
            <form method="post" action="/logout" style="display:inline;">
                <input type="hidden" name="_csrf_token" value="<?php echo htmlspecialchars((string) App\Support\SessionManager::csrfToken(), ENT_QUOTES, 'UTF-8'); ?>">
                <button type="submit" style="background:#dc2626;color:white;border:0;border-radius:8px;padding:10px 14px;cursor:pointer;">Sair</button>
            </form>
        </div>
    </header>

    <main class="shell">
        <div class="header">
            <div>
                <div class="muted" style="font-weight:700; letter-spacing:.08em; text-transform:uppercase; font-size:.76rem;">Administração</div>
                <h1>Usuários e permissões</h1>
            </div>
            <a class="secondary" href="/dashboard" style="display:inline-block; padding:10px 14px; border-radius:8px;">Dashboard</a>
        </div>

        <?php if (!empty($error)): ?>
            <div class="notice error"><?php echo htmlspecialchars((string) $error, ENT_QUOTES, 'UTF-8'); ?></div>
        <?php endif; ?>

        <?php if (!empty($success)): ?>
            <div class="notice success"><?php echo htmlspecialchars((string) $success, ENT_QUOTES, 'UTF-8'); ?></div>
        <?php endif; ?>

        <div class="grid">
            <section class="panel">
                <h2 style="margin-top:0;">Criar novo usuário</h2>
                <form method="post" action="/admin/users">
                    <input type="hidden" name="_csrf_token" value="<?php echo htmlspecialchars((string) $csrf, ENT_QUOTES, 'UTF-8'); ?>">
                    <div class="fields">
                        <label>
                            Usuário
                            <input type="text" name="username" placeholder="ex.: analista" required>
                        </label>
                        <label>
                            Senha
                            <input type="password" name="password" placeholder="Mínimo 6 caracteres" required>
                        </label>
                        <label>
                            Perfil
                            <select name="profile">
                                <option value="USER">Usuário</option>
                                <option value="MANAGER">Gerente</option>
                                <option value="ADMIN">Administrador</option>
                            </select>
                        </label>
                        <fieldset>
                            <legend>Permissões</legend>
                            <div class="checkboxes">
                                <?php foreach ($permissions as $permission): ?>
                                    <label>
                                        <input type="checkbox" name="permissions[]" value="<?php echo htmlspecialchars($permission, ENT_QUOTES, 'UTF-8'); ?>">
                                        <?php echo htmlspecialchars(strtoupper($permission), ENT_QUOTES, 'UTF-8'); ?>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        </fieldset>
                        <button class="primary" type="submit">Salvar usuário</button>
                    </div>
                </form>
            </section>

            <section class="panel">
                <h2 style="margin-top:0;">Usuários cadastrados</h2>
                <div style="overflow-x:auto;">
                    <table>
                        <thead>
                            <tr>
                                <th>Usuário</th>
                                <th>Perfil</th>
                                <th>Permissões</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $entry): ?>
                                <tr>
                                    <td>
                                        <strong><?php echo htmlspecialchars((string) ($entry['username'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></strong>
                                        <div class="muted"><?php echo htmlspecialchars((string) ($entry['status'] ?? 'ACTIVE'), ENT_QUOTES, 'UTF-8'); ?></div>
                                    </td>
                                    <td>
                                        <form method="post" action="/admin/users/<?php echo (int) ($entry['id'] ?? 0); ?>/permissions">
                                            <input type="hidden" name="_csrf_token" value="<?php echo htmlspecialchars((string) $csrf, ENT_QUOTES, 'UTF-8'); ?>">
                                            <select name="profile">
                                                <option value="USER" <?php echo (($entry['profile'] ?? 'USER') === 'USER') ? 'selected' : ''; ?>>Usuário</option>
                                                <option value="MANAGER" <?php echo (($entry['profile'] ?? 'USER') === 'MANAGER') ? 'selected' : ''; ?>>Gerente</option>
                                                <option value="ADMIN" <?php echo (($entry['profile'] ?? 'USER') === 'ADMIN') ? 'selected' : ''; ?>>Administrador</option>
                                            </select>
                                    </td>
                                    <td>
                                        <div class="checkboxes" style="grid-template-columns: repeat(auto-fit, minmax(110px, 1fr));">
                                            <?php foreach ($permissions as $permission): ?>
                                                <label>
                                                    <input type="checkbox" name="permissions[]" value="<?php echo htmlspecialchars($permission, ENT_QUOTES, 'UTF-8'); ?>" <?php echo in_array($permission, $entry['permissions'] ?? [], true) ? 'checked' : ''; ?>>
                                                    <?php echo htmlspecialchars(strtoupper($permission), ENT_QUOTES, 'UTF-8'); ?>
                                                </label>
                                            <?php endforeach; ?>
                                        </div>
                                    </td>
                                    <td>
                                        <button class="primary" type="submit">Salvar</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </section>
        </div>
    </main>
</body>
</html>
