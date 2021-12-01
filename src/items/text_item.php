<?php
final class TextItem extends FileItem
{
    use Getter;

    private $_title;
    private $_content;

    public function __construct($file)
    {
        $lines = file($file, FILE_IGNORE_NEW_LINES);
        $this->_title = array_shift($lines);
        $this->_content = $this->render($lines);
    }

    private function render($lines)
    {
        $html = '<div class="text">' . PHP_EOL;
        $html .= '<h1>' . $this->_title . '</h1>' . PHP_EOL;
        $pOpen = false;
        foreach ($lines as &$line) {
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
        $html .= '</div>' . PHP_EOL;
        $html = Text::markTitle($html);
        return $html;
    }
}
