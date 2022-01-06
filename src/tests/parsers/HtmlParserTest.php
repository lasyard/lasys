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
        $html =
            <<<'EOS'
            <h1>标题</h1>
            <img src="ha.jpg"/>
            EOS;
        $htmlParser = HtmlParser::str($html);
        $this->assertSame('标题', $htmlParser->title);
        $this->assertSame(
            <<<'EOS'
            <div id="html-body"><h1>标题</h1>
            <img src="ha.jpg?_type_=raw"></div>
            EOS,
            $htmlParser->content
        );
    }

    public function testFullHtml()
    {
        $html =
            <<<'EOS'
            <html>
            <head><title>MyTitle1</title></head>
            <body>
            <h1>MyTitle2</h1>
            </body>
            EOS;
        $htmlParser = HtmlParser::str($html);
        $this->assertSame('MyTitle1', $htmlParser->title);
        $this->assertSame(
            <<<'EOS'
            <div id="html-body">
            <h1>MyTitle2</h1>
            </div>
            EOS,
            $htmlParser->content
        );
    }
}
