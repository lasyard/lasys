<?php

use PHPUnit\Framework\TestCase;

final class MdParserTest extends TestCase
{
    public static function setUpBeforeClass(): void
    {
        require_once 'src/setup.php';
    }

    public function test()
    {
        $text = join(PHP_EOL, [
            '# TiTle',
            '## T2',
            '- haha',
            '- hehe',
            '### T3',
        ]);
        $mdParser = MdParser::str($text);
        $this->assertSame('TiTle', $mdParser->title);
        $this->assertSame(
            join(PHP_EOL, [
                '<div id="html-body"><h1>TiTle</h1>',
                '<h2>T2</h2>',
                '<ul>',
                '<li>haha</li>',
                '<li>hehe',
                '<h3>T3</h3></li>',
                '</ul></div>',
            ]),
            $mdParser->content
        );
    }
}
