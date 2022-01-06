<?php

use PHPUnit\Framework\TestCase;

final class TextParserTest extends TestCase
{
    public static function setUpBeforeClass(): void
    {
        require_once 'src/setup.php';
    }

    public function test()
    {
        $text = <<<'EOS'
        MyTitle

        haha

        1. abc

        def

        1.1 abcd

        abcd
        efgh

        2. ghi

        jkl

        see http://lasys
        EOS;
        $textParser = TextParser::str($text);
        $this->assertSame('MyTitle', $textParser->title);
        $this->assertSame(<<<'EOS'
        <div class="text">
        <h1>MyTitle</h1>
        <p>haha</p>
        <h2>1. abc</h2>
        <p>def</p>
        <h3>1.1 abcd</h3>
        <p>abcd<br />
        efgh</p>
        <h2>2. ghi</h2>
        <p>jkl</p>
        <p>see <a href="http://lasys" target="_blank">http://lasys</a></p>
        </div>
        EOS, $textParser->content);
    }
}
