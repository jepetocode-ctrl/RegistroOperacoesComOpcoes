<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Alterar senha | Registro de Operações com Opções</title>
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; font-family: Arial, sans-serif; background: #f8fafc; color: #0f172a; min-height: 100vh; display: grid; place-items: center; }
        .panel { width: min(500px, 92vw); background: #fff; border-radius: 16px; box-shadow: 0 16px 50px rgba(15, 23, 42, 0.08); padding: 28px; }
        h1 { margin-top: 0; }
        .field { margin-bottom: 16px; }
        label { display: block; margin-bottom: 8px; font-weight: 700; }
        input { width: 100%; padding: 12px; border: 1px solid #cbd5e1; border-radius: 10px; }
        .requirements { background: #eff6ff; border-left: 4px solid #2563eb; padding: 10px 12px; border-radius: 10px; margin-bottom: 18px; color: #1e3a8a; }
        .message { background: #fee2e2; color: #991b1b; border-radius: 10px; padding: 11px 12px; margin-bottom: 16px; }
        button { width: 100%; padding: 13px; border: none; background: #2563eb; color: white; border-radius: 10px; font-weight: 700; cursor: pointer; }
    </style>
</head>
<body>
    <div class="panel">
        <h1>Alterar senha</h1>
        <div class="requirements">
            A senha deve ter pelo menos 8 caracteres, com letras maiúsculas, minúsculas e números.
        </div>

        <?php if (!empty($error)): ?>
            <div class="message"><?php echo htmlspecialchars((string) $error); ?></div>
        <?php endif; ?>

        <form method="post" action="/change-password">
            <input type="hidden" name="_csrf_token" value="<?php echo htmlspecialchars((string) $csrf); ?>">

            <div class="field">
                <label for="current_password">Senha atual</label>
                <input id="current_password" name="current_password" type="password" required>
            </div>

            <div class="field">
                <label for="new_password">Nova senha</label>
                <input id="new_password" name="new_password" type="password" required>
            </div>

            <div class="field">
                <label for="confirm_password">Confirmação da nova senha</label>
                <input id="confirm_password" name="confirm_password" type="password" required>
            </div>

            <button type="submit">Salvar nova senha</button>
        </form>
    </div>
</body>
</html>
