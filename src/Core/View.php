<?php

declare(strict_types=1);

namespace App\Core;

final class View
{
    public static function render(string $view, array $data = []): void
    {
        $viewFile = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . $view . '.php';
        if (!file_exists($viewFile)) {
            http_response_code(500);
            echo 'View not found: ' . e($view);
            return;
        }

        extract($data, EXTR_SKIP);
        require $viewFile;
    }
}
