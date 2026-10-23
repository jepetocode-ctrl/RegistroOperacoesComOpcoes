<?php

declare(strict_types=1);

return [
    'name' => getenv('APP_NAME') ?: 'Registro de Operações com Opções',
    'env' => getenv('APP_ENV') ?: 'local',
    'debug' => filter_var(getenv('APP_DEBUG') ?: 'true', FILTER_VALIDATE_BOOLEAN),
    'url' => getenv('APP_URL') ?: 'http://localhost:8000',
    'timezone' => getenv('APP_TIMEZONE') ?: 'America/Sao_Paulo',
    'session' => [
        'lifetime' => (int) (getenv('SESSION_LIFETIME') ?: '1800'),
        'secure' => filter_var(getenv('SESSION_SECURE') ?: 'false', FILTER_VALIDATE_BOOLEAN),
        'same_site' => getenv('SESSION_SAME_SITE') ?: 'Lax',
        'cookie_name' => getenv('SESSION_COOKIE_NAME') ?: 'registro_opcoes_session',
    ],
    'csrf' => [
        'secret' => getenv('CSRF_SECRET') ?: 'change-this-secret',
    ],
    'log_path' => getenv('LOG_PATH') ?: dirname(__DIR__) . '/storage/logs/app.log',
];
