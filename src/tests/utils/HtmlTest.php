<?php

use PHPUnit\Framework\TestCase;

final class HtmlTest extends TestCase
{
    public static function setUpBeforeClass(): void
    {
        require_once 'src/setup.php';
    }

    public function testScriptLink()
    {
        $this->assertSame(
            '<script type="text/javascript" src="jquery"></script>' . PHP_EOL,
            Html::scriptLink('jquery')
        );
    }

    public function testCssLink()
    {
        $this->assertSame(
            '<link rel="stylesheet" href="jquery" type="text/css" />' . PHP_EOL,
            Html::cssLink('jquery')
        );
    }

    public function testLink()
    {
        $this->assertSame(
            '<a href="http://lasys.org" target="_blank">lasys</a>',
            Html::link(['text' => 'lasys', 'url' => 'http://lasys.org', 'target' => '_blank'])
        );
    }
}
