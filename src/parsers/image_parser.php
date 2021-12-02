<?php
final class ImageParser
{
    use Getter;

    private $_title;
    private $_content;

    public function __construct($url)
    {
        $this->_content = '<img src="' . $url . '?raw=1" />';
    }
}
