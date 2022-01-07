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

    private function content()
    {
        $lines = $this->_lines;
        $html = '<div class="text">' . PHP_EOL;
        $html .= '<h1>' . $this->_title . '</h1>' . PHP_EOL;
        $pOpen = false;
        foreach ($lines as $line) {
            if (empty(trim($line))) {
                if ($pOpen) {
                    $html .= "</p>" . PHP_EOL;
                    $pOpen = false;
                }
            } else {
                if (!$pOpen) {
                    $html .= "<p>";
                    $pOpen = true;
                } else {
                    $html .= "<br />" . PHP_EOL;
                }
                $html .= Str::filterLinks(htmlspecialchars($line));
            }
        }
        if ($pOpen) {
            $html .= "</p>" . PHP_EOL;
            $pOpen = false;
        }
        $html .= '</div>';
        $html = Text::markTitle($html);
        return $html;
    }
}
