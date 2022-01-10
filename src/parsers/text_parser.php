<?php
final class TextParser
{
    use Getter;

    private $_title;

    private $_lines;

    private function __construct($lines)
    {
        $this->_title = array_shift($lines);
        $this->_lines = $lines;
    }

    public static function file($file)
    {
        $lines = file($file, FILE_IGNORE_NEW_LINES);
        return new TextParser($lines);
    }

    public static function str($str)
    {
        $lines = explode(PHP_EOL, $str);
        return new TextParser($lines);
    }

    private static function packLines($lines)
    {
        $html = '';
        $c = count($lines);
        if ($c == 1) {
            $html .= '<p>' . $lines[0] . '</p>' . PHP_EOL;
        } else if ($c > 1) {
            $html .= '<ul>' . PHP_EOL;
            foreach ($lines as $line) {
                $html .= '<li>' . $line . '</li>' . PHP_EOL;
            }
            $html .= '</ul>' . PHP_EOL;
        }
        return $html;
    }

    private function content()
    {
        $lines = $this->_lines;
        $html = '<div class="text">' . PHP_EOL;
        $html .= '<h1>' . $this->_title . '</h1>' . PHP_EOL;
        $cLines = [];
        foreach ($lines as $line) {
            if (empty(trim($line))) {
                $html .= self::packLines($cLines);
                $cLines = [];
            } else {
                $cLines[] = Str::filterLinks(htmlspecialchars($line));
            }
        }
        $html .= self::packLines(($cLines));
        $html .= '</div>';
        $html = Text::markTitle($html);
        return $html;
    }
}
