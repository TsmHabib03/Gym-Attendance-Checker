<?php

declare(strict_types=1);

namespace App\Core;

final class View
{
    public static function render(string $view, array $data = []): void
    {
        // SECURITY: Whitelist the view name shape — only letters, digits,
        // underscores, dashes and forward slashes. This blocks path traversal
        // (../) and absolute paths from sneaking into require().
        if ($view === '' || !preg_match('#^[A-Za-z0-9_\-/]+$#', $view) || str_contains($view, '..')) {
            http_response_code(500);
            echo 'Invalid view.';
            return;
        }

        $viewsRoot = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR;
        $viewFile = $viewsRoot . str_replace('/', DIRECTORY_SEPARATOR, $view) . '.php';

        // Defense in depth: confirm the resolved real path is still inside
        // the views/ directory.
        $realRoot = realpath($viewsRoot);
        $realFile = realpath($viewFile);
        if ($realRoot === false || $realFile === false || !str_starts_with($realFile, $realRoot)) {
            http_response_code(500);
            echo 'View not found.';
            return;
        }

        // EXTR_SKIP refuses to overwrite existing local variables ($view,
        // $viewFile, etc.), preventing controllers from accidentally
        // shadowing internals via the data array.
        extract($data, EXTR_SKIP);
        require $realFile;
    }
}
