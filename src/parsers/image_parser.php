<?php
final class ImageParser
{
    use Getter;

    private $_content;

    private function __construct($url)
    {
        $this->_content = '<div class="center"><div class="pic">' . PHP_EOL
            . '<img src="' . $url . '?' . Server::QUERY_GET_RAW . '" />' . PHP_EOL
            . '</div></div>';
    }

    public static function url($url)
    {
        return new ImageParser($url);
    }
}
