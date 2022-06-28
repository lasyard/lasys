<?php

use PHPUnit\Framework\TestCase;

final class TextParserTest extends TestCase
{
    public static function setUpBeforeClass(): void
    {
        require_once 'src/setup.php';
    }

    public function testProcessLinesPoem()
    {
        $text = [
            'abcdef',
            'ghijkl',
        ];
        $this->assertSame(join(PHP_EOL, [
            '<p class="center">',
            'abcdef<br>',
            'ghijkl<br>',
            '</p>',
        ]), TextParser::processLines($text));
    }

    public function testProcessLinesOl()
    {
        $text = [
            '1 abcdef',
            '2 ghijkl',
        ];
        $this->assertSame(join(PHP_EOL, [
            '<ol>',
            '<li>abcdef</li>',
            '<li>ghijkl</li>',
            '</ol>',
        ]), TextParser::processLines($text));
    }

    public function testProcessLinesTable()
    {
        $text = [
            '1: abcdef',
            '2: ghijkl',
        ];
        $this->assertSame(join(PHP_EOL, [
            '<table>',
            '<tr><td align="right">1:</td><td align="left">abcdef</td></tr>',
            '<tr><td align="right">2:</td><td align="left">ghijkl</td></tr>',
            '</table>',
        ]), TextParser::processLines($text));
    }

    public function testProcessLinesNorm()
    {
        $text = [
            '1: abcdef',
            '2. ghijkl',
        ];
        $this->assertSame(join(PHP_EOL, [
            '<ul>',
            '<li>1: abcdef</li>',
            '<li>2. ghijkl</li>',
            '</ul>',
        ]), TextParser::processLines($text));
    }

    public function test()
    {
        $text = join(PHP_EOL, [
            'MyTitle',
            '',
            'haha',
            '',
            '1. abc',
            '',
            'def',
            '',
            '1.1 abcd',
            '',
            'abcd',
            'efgh',
            '',
            '2. ghi',
            '',
            'jkl',
            '',
            'see http://lasys',
        ]);
        $textParser = TextParser::str($text);
        $this->assertSame('MyTitle', $textParser->title);
        $this->assertSame(
            join(PHP_EOL, [
                '<div class="text">',
                '<h1>MyTitle</h1>',
                '<p>haha</p>',
                '<h2>1. abc</h2>',
                '<p>def</p>',
                '<h3>1.1 abcd</h3>',
                '<p class="center">',
                'abcd<br>',
                'efgh<br>',
                '</p>',
                '<h2>2. ghi</h2>',
                '<p>jkl</p>',
                '<p>see <a href="http://lasys" target="_blank">http://lasys</a></p>',
                '</div>',
            ]),
            $textParser->content
        );
    }

    public function testKatex()
    {
        $text = join(PHP_EOL, [
            'TestPre',
            '',
            'haha',
            '$$ x = 2 $$',
            'hehe',
            '$$',
            '1. abcd',
            '2. efgh',
            '$$',
        ]);
        $textParser = TextParser::str($text);
        $this->assertSame('TestPre', $textParser->title);
        $this->assertSame(
            join(PHP_EOL, [
                '<div class="text">',
                '<h1>TestPre</h1>',
                '<p>haha</p>',
                '$$ x = 2 $$',
                '<p>hehe</p>',
                '$$',
                '1. abcd',
                '2. efgh',
                '$$',
                '</div>',
            ]),
            $textParser->content
        );
    }
}
