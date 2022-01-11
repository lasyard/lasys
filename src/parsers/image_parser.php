<?php
final class ImageParser
{
    use Getter;

    private $_content;
    private $_info;

    private function __construct($file, $url)
    {
        list($w, $h, $t) = getimagesize($file);
        $this->_info = ' ' . Icon::INFO . $w . 'x' . $h . ' ' . image_type_to_extension($t, false);
        $this->_content = '<div class="center"><div class="pic">' . PHP_EOL
            . '<img src="' . $url . '?' . Server::QUERY_GET_RAW . '" />' . PHP_EOL
            . '</div></div>';
    }

    public static function fileUrl($file, $url)
    {
        return new ImageParser($file, $url);
    }
}
