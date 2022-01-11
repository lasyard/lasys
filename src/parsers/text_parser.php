<?php
final class TextParser
{
    use Getter;

    // comma, period, colon, exclamation, question
    private const NOT_PUNC = '[^\x{FF0C}\x{3002}\x{FF1A}\x{FF01}\x{FF1F},.:!?;=<>_]';
    private const POEM_LINE_THRESHOLD = 40;

    private $_title;
    private $_info;

    private $_lines;

    private function __construct($lines)
    {
        $numChars = 0;
        foreach ($lines as $line) {
            $numChars += mb_strlen($line, 'UTF-8');
        }
        $this->_info = ' ' . Icon::INFO . $numChars . ' characters';
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

    private static function headerLevel($line)
    {
        if (preg_match('/^(\d+(?:\.\d+)*\.?)\s+' . self::NOT_PUNC . '+$/u', $line, $matches)) {
            return  2 + count(explode('.', rtrim($matches[1], '.'))) - 1;
        }
        return 0;
    }

    public static function processLines($lines)
    {
        $nLines = [];
        $type = null;
        foreach ($lines as $line) {
            if (preg_match('/^\d+\.?\s+(.*)$/', $line, $matches)) {
                $nLines[] = $matches[1];
                $type ??= 'ol';
                if ($type !== 'ol') {
                    $type = 'norm';
                    break;
                }
            } else if (preg_match('/^([^\x{FF1A}:]+[\x{FF1A}:])\s*(.*)/u', $line, $matches)) {
                $nLines[] = '<tr><td align="right">' . $matches[1] . '</td>'
                    . '<td align="left">' . $matches[2] . '</td></tr>';
                $type ??= 'table';
                if ($type !== 'table') {
                    $type = 'norm';
                    break;
                }
            } else if (mb_strlen($line, 'UTF-8') <= self::POEM_LINE_THRESHOLD) {
                $type ??= 'poem';
                if ($type !== 'poem') {
                    $type = 'norm';
                    break;
                }
            }
        }
        switch ($type) {
            case 'ol':
                $html = '<ol>' . PHP_EOL;
                foreach ($nLines as $line) {
                    $html .= '<li>' . $line . '</li>' . PHP_EOL;
                }
                $html .= '</ol>';
                break;
            case 'table':
                $html = '<table>' . PHP_EOL;
                foreach ($nLines as $line) {
                    $html .=  $line . PHP_EOL;
                }
                $html .= '</table>';
                break;
            case 'poem':
                $html = '<p class="center">' . PHP_EOL;
                foreach ($lines as $line) {
                    $html .=  $line . '<br>' . PHP_EOL;
                }
                $html .= '</p>';
                break;
            default:
                $html = '<ul>' . PHP_EOL;
                foreach ($lines as $line) {
                    $html .= '<li>' . $line . '</li>' . PHP_EOL;
                }
                $html .= '</ul>';
        }
        return $html;
    }

    private static function packLines($lines)
    {
        $html = '';
        $c = count($lines);
        if ($c == 1) {
            $line = $lines[0];
            $lv = self::headerLevel($line);
            if ($lv == 0) {
                $html .= '<p>' . $line . '</p>' . PHP_EOL;
            } else {
                $html .= '<h' . $lv . '>' . $line . '</h' . $lv . '>' . PHP_EOL;
            }
        } else if ($c > 1) {
            $html .= self::processLines($lines) . PHP_EOL;
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
            $line = trim($line);
            if (empty($line)) {
                $html .= self::packLines($cLines);
                $cLines = [];
            } else {
                $cLines[] = Str::filterLinks(htmlspecialchars(Str::filterEmoji($line)));
            }
        }
        $html .= self::packLines(($cLines));
        $html .= '</div>';
        return $html;
    }
}
