<?php

declare(strict_types=1);

namespace App\Controllers;

abstract class BaseController
{
    protected function view(string $template, array $data = []): string
    {
        extract($data);
        ob_start();
        require dirname(__DIR__, 2) . '/resources/views/' . $template . '.php';
        return (string) ob_get_clean();
    }
}
