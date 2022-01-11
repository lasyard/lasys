<?php
final class ImageParser
{
    use Getter;

    private $_content;
    private $_info;

    private function __construct($url, $info)
    {
        $this->_info = $info;
        $this->_content = '<div class="center"><div class="pic">' . PHP_EOL
            . '<img src="' . $url . '?' . Server::QUERY_GET_RAW . '" />' . PHP_EOL
            . '</div></div>';
    }

    public static function url($url)
    {
        return new ImageParser($url, null);
    }

    public static function fileUrl($file, $url)
    {
        list($w, $h, $t) = getimagesize($file);
        $info = ' ' . Icon::INFO . $w . 'x' . $h . ' ' . image_type_to_extension($t, false);
        return new ImageParser($url, $info);
    }
}
