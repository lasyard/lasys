<?php
final class View
{
    private function __construct()
    {
    }

    public static function renderHtml($layout, $vars = [])
    {
        ob_start();
        self::render($layout, $vars);
        return ob_get_clean();
    }

    public static function render($layout, $vars = [])
    {
        extract($vars);
        if (is_file(VIEWS_PATH . '/' . $layout . '.php')) { // In user view path.
            require VIEWS_PATH . '/' . $layout . '.php';
        } else if (is_file(__DIR__ . '/../views/' . $layout . '.php')) { // In sys view path.
            require 'views/' . $layout . '.php';
        }
    }
}
