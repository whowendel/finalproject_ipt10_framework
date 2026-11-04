<?php

declare(strict_types=1);

namespace FlowForge\Core;

use FlowForge\Support\Csrf;

/**
 * Native PHP View Renderer.
 * Supports template rendering with data extraction, optional layouts,
 * HTML escaping, and CSRF helpers.
 */
class View
{
    private static string $viewsPath;

    public static function setViewsPath(string $path): void
    {
        self::$viewsPath = rtrim($path, '/\\');
    }

    public static function getViewsPath(): string
    {
        if (!isset(self::$viewsPath)) {
            self::$viewsPath = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'views';
        }
        return self::$viewsPath;
    }

    /**
     * Escape output safely with htmlspecialchars.
     */
    public static function e(mixed $value): string
    {
        return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
    }

    /**
     * Render a view file inside an optional layout.
     */
    public static function render(string $template, array $data = [], ?string $layout = 'layouts/main'): string
    {
        $viewFile = self::getViewsPath() . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $template) . '.php';

        if (!file_exists($viewFile)) {
            throw new \RuntimeException("View template not found: {$template} ({$viewFile})");
        }

        // Add view helper functions into scope
        $data['user'] = $_SESSION['user'] ?? null;
        $data['flash'] = $_SESSION['_flash'] ?? [];

        // Extract variables for the template
        $content = self::renderFile($viewFile, $data);

        // Clear one-time flash messages after rendering
        unset($_SESSION['_flash']);

        if ($layout === null) {
            return $content;
        }

        $layoutFile = self::getViewsPath() . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $layout) . '.php';
        if (!file_exists($layoutFile)) {
            throw new \RuntimeException("Layout not found: {$layout} ({$layoutFile})");
        }

        $layoutData = array_merge($data, ['content' => $content]);
        return self::renderFile($layoutFile, $layoutData);
    }

    private static function renderFile(string $file, array $data): string
    {
        extract($data, EXTR_SKIP);

        ob_start();
        try {
            require $file;
            return ob_get_clean() ?: '';
        } catch (\Throwable $e) {
            ob_end_clean();
            throw $e;
        }
    }
}
