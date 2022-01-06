<?php
class HtmlParser
{
    use Getter;

    private $_dom;
    private $_xpath;

    private function __construct($dom)
    {
        $this->_dom = $dom;
        $this->_xpath = new DOMXPath($dom);
    }

    public static function file($file)
    {
        $dom = new DOMDocument();
        $dom->loadHTMLFile($file);
        return new HtmlParser($file);
    }

    public static function str($str)
    {
        $dom = new DOMDocument();
        // Add `/html/body` automatically.
        $dom->loadHTML('<meta http-equiv="Content-Type" content="text/html; charset=utf-8">' . $str);
        return new HtmlParser($dom);
    }

    private function title()
    {
        $xpath = $this->_xpath;
        $nodes = $xpath->query('/html/head/title');
        if ($nodes->length == 0) {
            $nodes = $xpath->query('/html/body/h1');
        }
        if ($nodes->length > 0) {
            return $nodes->item(0)->textContent;
        }
        return null;
    }

    private function content()
    {
        $dom = $this->_dom;
        $body = $dom->getElementsByTagName('body')->item(0);
        $imgs = $body->getElementsByTagName('img');
        foreach ($imgs as $img) {
            if (!$img->hasAttribute('src')) {
                continue;
            }
            $url = $img->getAttribute('src');
            $img->setAttribute('src', Server::rawUrl($url));
        }
        return str_ireplace(
            ['<body>', '</body>'],
            ['<div id="html-body">', '</div>'],
            $dom->saveHTML($body)
        );
    }

    private function scripts()
    {
        $xpath = $this->_xpath;
        $scripts = self::queryAttr($xpath, '/html/head/script/@src');
        return array_map(function ($u) {
            return Server::rawUrl($u);
        }, $scripts);
    }

    private function styles()
    {
        $xpath = $this->_xpath;
        $styles = self::queryAttr($xpath, '/html/head/link[@rel="stylesheet"]/@href');
        return array_map(function ($u) {
            return Server::rawUrl($u);
        }, $styles);
    }

    private function css()
    {
        $xpath = $this->_xpath;
        $styles = $xpath->query('/html/head/style');
        $result = '';
        foreach ($styles as $style) {
            $result .= trim($style->textContent);
        }
        return $result;
    }

    private static function queryAttr($xpath, $str)
    {
        $nodes = $xpath->query($str);
        $values = [];
        foreach ($nodes as $node) {
            $values[] = $node->value;
        }
        return $values;
    }
}
