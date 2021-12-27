<?php
final class ImageParser
{
    use Getter;

    private $_title;
    private $_content;

    public function __construct($url)
    {
        $this->_content = '<img src="' . $url . Server::QUERY_GET_RAW . '" />';
    }
}
