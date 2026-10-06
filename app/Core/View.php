<?php

namespace App\Core;

final class View
{
    /** Named stacks (e.g. "styles", "scripts") that views push to and layouts print. */
    private static array $stacks = [];

    public static function push(string $stack, string $value): void
    {
        if (!in_array($value, self::$stacks[$stack] ?? [], true)) {
            self::$stacks[$stack][] = $value;
        }
    }

    public static function stack(string $stack): array
    {
        return self::$stacks[$stack] ?? [];
    }

    public static function render(string $view, array $data = [], ?string $layout = null): string
    {
        $content = self::renderFile($view, $data);

        if ($layout === null) {
            return $content;
        }

        return self::renderFile('layouts/' . $layout, array_merge($data, ['content' => $content]));
    }

    public static function renderFile(string $view, array $data = []): string
    {
        $file = BASE_PATH . '/app/Views/' . $view . '.php';
        if (!is_file($file)) {
            throw new \RuntimeException("View [$view] not found.");
        }

        // Include from a closure so the view only sees its own variables (no $view/$file/$data clashes).
        $render = static function (string $__file, array $__vars): void {
            extract($__vars, EXTR_SKIP);
            include $__file;
        };

        ob_start();
        try {
            $render($file, $data);
        } catch (\Throwable $e) {
            ob_end_clean();
            throw $e;
        }

        return (string) ob_get_clean();
    }
}
