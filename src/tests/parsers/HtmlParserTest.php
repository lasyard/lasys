<?php

use PHPUnit\Framework\TestCase;

final class HtmlParserTest extends TestCase
{
    public static function setUpBeforeClass(): void
    {
        require_once 'src/setup.php';
    }

    public function testRawHtml()
    {
        $html = join(PHP_EOL, [
            '<h1>标题</h1>',
            '<img src="ha.jpg"/>',
        ]);
        $htmlParser = HtmlParser::str($html);
        $this->assertSame('标题', $htmlParser->title);
        $this->assertSame(
            join(PHP_EOL, [
                '<div id="html-body"><h1>标题</h1>',
                '<img src="ha.jpg?_type_=raw"></div>',
            ]),
            $htmlParser->content
        );
    }

    public function testRawHtml1()
    {
        $html = join(PHP_EOL, [
            '<body>',
            '<h1>MyTitle2</h1>',
            '</body>',
        ]);
        $htmlParser = HtmlParser::str($html);
        $this->assertSame('MyTitle2', $htmlParser->title);
        $this->assertSame(
            join(PHP_EOL, [
                '<div id="html-body">',
                '<h1>MyTitle2</h1>',
                '</div>',
            ]),
            $htmlParser->content
        );
    }
}
