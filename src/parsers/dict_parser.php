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
            list($key, $value) = explode("\t", $line, 2);
            $dict[$key] = $value;
        }
        return new DictParser($dict);
    }

    private function content()
    {
        $html = '<table>' . PHP_EOL;
        $html .= '<colgroup><col /><col /></colgroup>' . PHP_EOL;
        $html .= '<tr><th>Key</th><th>Value</th></tr>' . PHP_EOL;
        foreach ($this->_dict as $key => $value) {
            $html .= '<tr><td>' . $key . '</td><td>' . $value . '</td></tr>' . PHP_EOL;
        }
        $html .= '</table>' . PHP_EOL;
        return $html;
    }
}
