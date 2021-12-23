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

    public static function link($text, $url, $title = null, $target = null)
    {
        $html = '<a href="' . $url . '"';
        $html .= (isset($title) ? ' title="' . $title . '"' : '');
        $html .= (isset($target) ? ' target="' . $target . '"' : '');
        $html .= '>' . $text . '</a>';
        return $html;
    }

    public static function input($name, $type, $required = false, $attrs = [])
    {
        switch ($type) {
            case 'textarea':
                $html = '<textarea name="' . $name . '"';
                $end = '></textarea>';
                break;
            default:
                $html = '<input name="' . $name . '" type="' . $type . '"';
                $end = '></input>';
        }
        if ($required) {
            $html .= ' required';
        }
        foreach ($attrs as $key => $value) {
            $html .= ' ' . $key . '="' . $value . '"';
        }
        $html .= $end;
        return $html;
    }
}
