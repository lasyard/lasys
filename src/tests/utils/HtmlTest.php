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
            '<script type="text/javascript" src="jquery?v=0"></script>' . PHP_EOL,
            Html::scriptLink('jquery')
        );
    }

    public function testCssLink()
    {
        $this->assertSame(
            '<link rel="stylesheet" href="jquery?v=0" type="text/css" />' . PHP_EOL,
            Html::cssLink('jquery')
        );
    }

    public function testLink()
    {
        $this->assertSame(
            '<a href="http://lasys.org" target="_blank">lasys</a>',
            Html::link('lasys', 'http://lasys.org', null, '_blank')
        );
    }

    public function testInputText()
    {
        $this->assertSame(
            '<input name="name" type="text"></input>' . PHP_EOL,
            Html::input('name', 'text')
        );
        $this->assertSame(
            '<textarea name="text" required class="sys"></textarea>' . PHP_EOL,
            Html::input('text', 'textarea', true, ['class' => 'sys'])
        );
    }

    public function testInputSelect()
    {
        $this->assertSame(
            join(PHP_EOL, [
                '<select name="sel" required>',
                '<option value="">-- Choose an option --</option>',
                '<option value="a">a</option>',
                '<option value="b">b</option>',
                '<option value="c">c</option>',
                '</select>',
            ]),
            Html::input('sel', 'select', true, ['options' => ['a', 'b', 'c']])
        );
        $this->assertSame(
            join(PHP_EOL, [
                '<select name="sel" required>',
                '<option value="">-- Choose an option --</option>',
                '<option value="1">a</option>',
                '<option value="2">b</option>',
                '<option value="3">c</option>',
                '</select>',
            ]),
            Html::input('sel', 'select', true, ['options' => ['a' => 1, 'b' => 2, 'c' => 3]])
        );
    }
}
