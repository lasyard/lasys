<?php

use PHPUnit\Framework\TestCase;

final class MsgTest extends TestCase
{
    public static function setUpBeforeClass(): void
    {
        require_once 'src/setup.php';
    }

    public function testInfo()
    {
        $result = Common::getOutput(['Msg', 'info'], ['haha']);
        $this->assertSame(
            '<p class="center">' . Icon::INFO . ' haha</p>',
            $result
        );
    }

    public function testWarn()
    {
        $result = Common::getOutput(['Msg', 'warn'], ['hehe']);
        $this->assertSame(
            '<p class="hot center">' . Icon::WARN . ' hehe</p>',
            $result
        );
    }
}
