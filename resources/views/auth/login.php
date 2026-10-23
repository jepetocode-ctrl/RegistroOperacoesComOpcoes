<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login | Registro de Operações com Opções</title>
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; font-family: Arial, sans-serif; background: linear-gradient(135deg, #0f172a, #1d4ed8); color: #0f172a; min-height: 100vh; display: grid; place-items: center; }
        .panel { width: min(420px, 90vw); background: rgba(255,255,255,0.95); border-radius: 18px; box-shadow: 0 25px 60px rgba(15, 23, 42, 0.25); padding: 30px; }
        h1 { margin: 0 0 8px; font-size: 2rem; }
        p { margin-top: 0; color: #475569; }
        .field { margin-bottom: 16px; }
        label { display: block; margin-bottom: 8px; font-weight: 700; }
        input { width: 100%; padding: 12px 14px; border: 1px solid #cbd5e1; border-radius: 10px; font-size: 1rem; }
        .password-wrap { position: relative; }
        .password-wrap button { position: absolute; right: 10px; top: 50%; transform: translateY(-50%); border: none; background: transparent; cursor: pointer; font-weight: 700; color: #2563eb; }
        .actions { display: flex; justify-content: space-between; align-items: center; gap: 12px; }
        button[type="submit"] { width: 100%; background: #2563eb; color: #fff; border: none; border-radius: 10px; padding: 13px; font-size: 1rem; font-weight: 700; cursor: pointer; }
        .message { background: #fee2e2; color: #991b1b; border-radius: 10px; padding: 10px 12px; margin-bottom: 16px; }
        .meta { margin-top: 14px; font-size: 0.92rem; color: #475569; }
        .meta strong { color: #0f172a; }
    </style>
</head>
<body>
    <div class="panel">
        <h1>Entrar</h1>
        <p>Faça login para acessar a plataforma.</p>

        <?php if (!empty($error)): ?>
            <div class="message"><?php echo htmlspecialchars((string) $error); ?></div>
        <?php endif; ?>

        <form method="post" action="/login">
            <input type="hidden" name="_csrf_token" value="<?php echo htmlspecialchars((string) $csrf); ?>">

            <div class="field">
                <label for="username">Usuário</label>
                <input id="username" name="username" type="text" value="<?php echo htmlspecialchars((string) ($username ?? '')); ?>" required>
            </div>

            <div class="field">
                <label for="password">Senha</label>
                <div class="password-wrap">
                    <input id="password" name="password" type="password" required>
                    <button type="button" id="toggle-password" aria-label="Mostrar ou ocultar senha">Mostrar</button>
                </div>
            </div>

            <div class="actions">
                <button type="submit">Entrar</button>
            </div>
        </form>

        <div class="meta">
            <strong>Usuário inicial:</strong> administrador<br>
            <strong>Senha inicial:</strong> admin123
        </div>
    </div>

    <script>
        const toggle = document.getElementById('toggle-password');
        const passwordInput = document.getElementById('password');

        toggle.addEventListener('click', function () {
            const isHidden = passwordInput.type === 'password';
            passwordInput.type = isHidden ? 'text' : 'password';
            toggle.textContent = isHidden ? 'Ocultar' : 'Mostrar';
        });
    </script>
</body>
</html>
