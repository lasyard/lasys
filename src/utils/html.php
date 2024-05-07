<?php
final class Html
{
    private function __construct()
    {
    }

    public static function cssLink($css)
    {
        return '<link rel="stylesheet" href="' . $css . '?v=' . VERSION . '" type="text/css" />' . PHP_EOL;
    }

    public static function scriptLink($script)
    {
        return '<script type="text/javascript" src="' . $script . '?v=' . VERSION . '"></script>' . PHP_EOL;
    }

    public static function link($text, $url, $title = null, $target = null)
    {
        $html = '<a href="' . $url . '"';
        $html .= (isset($title) ? ' title="' . $title . '"' : '');
        $html .= (isset($target) ? ' target="' . $target . '"' : '');
        $html .= '>' . $text . '</a>';
        return $html;
    }

    private static function inlineAttrs($required, $attrs)
    {
        $html = '';
        if ($required) {
            $html .= ' required';
        }
        foreach ($attrs as $key => $value) {
            $html .= ' ' . $key . '="' . $value . '"';
        }
        return $html;
    }

    public static function input($name, $type, $required = false, $attrs = [])
    {
        switch ($type) {
            case 'select':
                if (isset($attrs['options'])) {
                    $options = $attrs['options'];
                    unset($attrs['options']);
                }
                $html = '<select name="' . $name . '"' . self::inlineAttrs($required, $attrs) . '>' . PHP_EOL;
                $html .= '<option value="">-- Choose an option --</option>' . PHP_EOL;
                if (isset($options)) {
                    foreach ($options as $option => $value) {
                        if (is_int($option)) { // A flat array.
                            $option = $value;
                        }
                        $html .= '<option value="' . $value . '">' . $option . '</option>' . PHP_EOL;
                    }
                }
                $html .= '</select>';
                break;
            case 'textarea':
                $html = '<textarea name="' . $name . '"' . self::inlineAttrs($required, $attrs) . '></textarea>' . PHP_EOL;
                break;
            case 'checkboxes':
                $items = $attrs['items'];
                unset($attrs['items']);
                $html = '<div class="checkbox">' . PHP_EOL;
                foreach ($items as $index => $label) {
                    $html .= '<span><input type="checkbox" name="' . $name . '[' . $index . ']" />' . $label . '</span>' . PHP_EOL;
                }
                $html .= '</div>';
                break;
            default:
                if (isset($attrs['dataList'])) {
                    $dataListId = uniqid("dl_");
                    $dataList = $attrs['dataList'];
                    unset($attrs['dataList']);
                    $attrs['list'] = $dataListId;
                }
                $html = '<input name="' . $name . '" type="' . $type . '"'
                    . self::inlineAttrs($required, $attrs) . '></input>' . PHP_EOL;
                if (isset($dataListId)) {
                    $html .= '<datalist id="' . $dataListId . '">' . PHP_EOL;
                    foreach ($dataList as $v => $title) {
                        $html .= '<option value="' . $v . '">' . $title . '</option>' . PHP_EOL;
                    }
                    $html .= '</datalist>' . PHP_EOL;
                }
        }
        return $html;
    }
}
