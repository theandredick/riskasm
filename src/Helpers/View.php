<?php

declare(strict_types=1);

namespace App\Helpers;

class View
{
    /**
     * Render a template file and wrap it in the main application layout.
     *
     * @param string $template  Path relative to /templates/, without .php
     * @param array  $data      Variables passed to both the template and the layout
     * @param string $layout    Layout name: 'base' (default) or 'auth'
     */
    public static function render(string $template, array $data = [], string $layout = 'base'): string
    {
        $content   = self::capture($template, $data);
        $pageTitle = $data['pageTitle'] ?? null;

        $layoutFile = APP_ROOT . '/templates/layout/' . $layout . '.php';

        ob_start();
        // $content and $pageTitle are available in the layout template
        include $layoutFile;
        return ob_get_clean();
    }

    /**
     * Render a template and return just its HTML, without any layout wrapper.
     */
    public static function partial(string $template, array $data = []): string
    {
        return self::capture($template, $data);
    }

    private static function capture(string $template, array $data): string
    {
        extract($data, EXTR_SKIP);
        ob_start();
        include APP_ROOT . '/templates/' . $template . '.php';
        return ob_get_clean();
    }
}
