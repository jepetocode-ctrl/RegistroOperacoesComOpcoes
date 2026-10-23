<?php

declare(strict_types=1);

namespace App\Support;

final class Request
{
    public static function method(): string
    {
        return strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
    }

    public static function uri(): string
    {
        return parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
    }

    public static function all(): array
    {
        $data = $_REQUEST;

        if (self::method() === 'POST' && self::isJson()) {
            $raw = file_get_contents('php://input');
            $decoded = json_decode((string) $raw, true);
            if (is_array($decoded)) {
                $data = array_merge($data, $decoded);
            }
        }

        return $data;
    }

    public static function input(string $key, mixed $default = null): mixed
    {
        $payload = self::all();
        return $payload[$key] ?? $default;
    }

    public static function isJson(): bool
    {
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
        return str_contains(strtolower($contentType), 'application/json');
    }
}
