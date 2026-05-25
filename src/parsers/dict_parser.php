<?php
final class DictParser
{
    use Getter;

    protected $_dict = [];

    private function __construct($dict)
    {
        $this->_dict = $dict;
    }

    public static function file($file)
    {
        $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $dict = [];
        foreach ($lines as $line) {
            @list($key, $mode, $value) = explode(":", $line, 3);
            $key = trim($key);
            if (!empty($key) && $mode !== null && $value !== null) {
                $dict[$key] = [
                    'mode' => $mode,
                    'value' => trim($value),
                ];
            }
        }
        return new DictParser($dict);
    }

    private function content()
    {
        $html = '<div id="html-body"><table>' . PHP_EOL;
        $html .= '<colgroup><col /><col /><col /></colgroup>' . PHP_EOL;
        $html .= '<tr><th>Key</th><th>Mode</th><th>Value</th></tr>' . PHP_EOL;
        foreach ($this->_dict as $key => list('mode' => $mode, 'value' => $value)) {
            $html .= '<tr><td>' . $key . '</td><td>' . $mode . '</td><td>' . $value . '</td></tr>' . PHP_EOL;
        }
        $html .= '</table></div>' . PHP_EOL;
        return $html;
    }
}
