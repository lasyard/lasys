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
        if (is_file(VIEWS_PATH . DS . $layout . '.php')) { // In user view path.
            require VIEWS_PATH . DS . $layout . '.php';
        } else if (is_file(__DIR__ . DS . '..' . DS . 'views' . DS . $layout . '.php')) { // In sys view path.
            require __DIR__ . DS . '..' . DS . 'views' . DS . $layout . '.php';
        }
    }
}
