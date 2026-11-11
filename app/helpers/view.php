<?php
/**
 * Helper rendering view sederhana tanpa template engine.
 */

declare(strict_types=1);

/**
 * Render sebuah view dengan data yang diberikan.
 */
function view(string $template, array $data = []): void
{
    $file = __DIR__ . '/../views/' . str_replace('.', '/', $template) . '.php';

    if (!is_file($file)) {
        http_response_code(500);
        exit('View tidak ditemukan.');
    }

    extract($data, EXTR_SKIP);
    require $file;
}

/**
 * Render view lalu bungkus dengan layout.
 */
function render(string $layout, string $template, array $data = []): void
{
    $contentFile = __DIR__ . '/../views/' . str_replace('.', '/', $template) . '.php';
    $layoutFile  = __DIR__ . '/../views/layouts/' . $layout . '.php';

    if (!is_file($contentFile) || !is_file($layoutFile)) {
        http_response_code(500);
        exit('Layout atau view tidak ditemukan.');
    }

    extract($data, EXTR_SKIP);

    ob_start();
    require $contentFile;
    $content = ob_get_clean();

    require $layoutFile;
}
