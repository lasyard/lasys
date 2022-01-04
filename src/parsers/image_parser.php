<?php
final class ImageParser
{
    use Getter;

    private $_title;
    private $_content;

    public function __construct($url)
    {
        $this->_content = '<div class="center"><div class="pic">' . PHP_EOL
            . '<img src="' . $url . '?' . Server::QUERY_GET_RAW . '" />' . PHP_EOL
            . '</div></div>';
    }
}
