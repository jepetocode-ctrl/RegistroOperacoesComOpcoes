<?php

declare(strict_types=1);

return [
    'name' => env('APP_NAME', 'Registro de Operações com Opções'),
    'env' => env('APP_ENV', 'local'),
    'debug' => (bool) env('APP_DEBUG', true),
    'url' => env('APP_URL', 'http://localhost:8000'),
    'timezone' => env('APP_TIMEZONE', 'America/Sao_Paulo'),
    'locale' => env('APP_LOCALE', 'pt_BR'),
    'fallback_locale' => env('APP_FALLBACK_LOCALE', 'pt_BR'),
    'faker_locale' => env('APP_FAKER_LOCALE', 'pt_BR'),
    'cipher' => 'AES-256-CBC',
    'key' => env('APP_KEY'),
    'previous_keys' => [],
    'maintenance' => ['driver' => 'file', 'store' => 'database'],
    'session' => [
        'lifetime' => (int) env('SESSION_LIFETIME', 1800),
        'secure' => (bool) env('SESSION_SECURE', false),
        'same_site' => env('SESSION_SAME_SITE', 'Lax'),
        'cookie_name' => env('SESSION_COOKIE_NAME', 'registro_opcoes_session'),
    ],
    'csrf' => [
        'secret' => env('CSRF_SECRET', 'change-this-secret'),
    ],
    'log_path' => env('LOG_PATH', dirname(__DIR__) . '/storage/logs/app.log'),
];
