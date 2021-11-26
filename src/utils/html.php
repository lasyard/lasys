<?php
final class Html
{
    private function __construct()
    {
    }

    public static function cssLink($css)
    {
        return '<link rel="stylesheet" href="' . $css . '" type="text/css" />' . PHP_EOL;
    }

    public static function scriptLink($script)
    {
        return '<script type="text/javascript" src="' . $script . '"></script>' . PHP_EOL;
    }

    public static function link($link, $attrs = [])
    {
        $html = '<a href="' . $link['url'] . '"';
        $html .= (!empty($link['title']) ? ' title="' . $link['title'] . '"' : '');
        $html .= (!empty($link['target']) ? ' target="' . $link['target'] . '"' : '');
        $html .= '>' . $link['text'] . '</a>';
        return $html;
    }

    public static function input($type, $name, $attrs = [])
    {
        if ($type == 'textarea') {
            $html = '<textarea name="' . $name . '"';
            foreach ($attrs as $key => $value) {
                $html .= ' ' . $key . '="' . $value . '"';
            }
            $html .= '></textarea>';
        } else {
            $html = '<input type="' . $type . '" name="' . $name . '"';
            foreach ($attrs as $key => $value) {
                $html .= ' ' . $key . '="' . $value . '"';
            }
            $html .= ' />';
        }
        return $html;
    }
}
