<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Support\Response;

final class HomeController extends BaseController
{
    public function index(): void
    {
        $html = <<<'HTML'
<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Registro de Operações com Opções</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f4f7fb; color: #1f2937; margin: 0; padding: 32px; }
        .card { max-width: 900px; margin: 0 auto; background: #fff; border-radius: 16px; padding: 32px; box-shadow: 0 10px 30px rgba(15, 23, 42, 0.08); }
        h1 { margin: 0 0 12px; }
        p { line-height: 1.6; }
        .status { display: inline-block; background: #ecfdf5; color: #065f46; padding: 8px 12px; border-radius: 999px; font-weight: bold; }
        ul { margin-top: 24px; }
        code { background: #eef2ff; padding: 2px 6px; border-radius: 6px; }
    </style>
</head>
<body>
    <div class="card">
        <div class="status">Sistema inicializado</div>
        <h1>Registro de Operações com Opções</h1>
        <p>Base de desenvolvimento para a plataforma modular de análise, registro e precificação de opções.</p>
        <p>Esta etapa implementa a fundação técnica, a estrutura de documentação e o bootstrap do projeto.</p>
        <ul>
            <li>Arquitetura em camadas preparada.</li>
            <li>Configuração de ambiente e banco.</li>
            <li>Roteamento e front controller.</li>
            <li>Documentação de continuidade e próximos passos.</li>
        </ul>
        <p>Próxima etapa: autenticação e segurança.</p>
    </div>
</body>
</html>
HTML;

        Response::html($html, 200);
    }

    public function health(): void
    {
        Response::json([
            'success' => true,
            'status' => 'ok',
            'app' => 'Registro de Operações com Opções',
            'environment' => getenv('APP_ENV') ?: 'local',
        ]);
    }

    public function apiStatus(): void
    {
        Response::json([
            'success' => true,
            'status' => 'api-online',
            'message' => 'API da plataforma em funcionamento.',
        ]);
    }
}
