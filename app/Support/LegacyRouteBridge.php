<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Http\Response;

final class LegacyRouteBridge
{
    public function dispatch(array $handler): Response
    {
        http_response_code(200);
        ob_start();

        if (is_callable($handler)) {
            $handler();
        } else {
            [$controllerClass, $method] = $handler;
            app($controllerClass)->{$method}();
        }

        $body = (string) ob_get_clean();
        $status = http_response_code();
        $headers = [];
        foreach (headers_list() as $header) {
            if (stripos($header, 'Content-Type:') === 0) {
                $headers['Content-Type'] = trim(substr($header, strlen('Content-Type:')));
            }
        }

        return response($body, $status >= 100 ? $status : 200, $headers);
    }
}