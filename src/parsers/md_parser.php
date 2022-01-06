<?php
require_once 'erusev/parsedown/Parsedown.php';

final class MdParser
{
    use Getter;

    private $_htmlParser;

    private function __construct($text)
    {
        $parsedown = new Parsedown();
        $parsedown->setSafeMode(true);
        $html = $parsedown->text($text);
        $this->_htmlParser = HtmlParser::str($html);
    }

    public static function file($file)
    {
        return new MdParser(file_get_contents($file));
    }

    public static function str($str)
    {
        return new MdParser($str);
    }

    private function title()
    {
        return $this->_htmlParser->title;
    }

    private function content()
    {
        return $this->_htmlParser->content;
    }
}
