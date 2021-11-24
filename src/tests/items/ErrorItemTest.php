<?php

use PHPUnit\Framework\TestCase;

final class ErrorItemTest extends TestCase
{
    public static function setUpBeforeClass(): void
    {
        require_once 'src/setup.php';
    }

    public function test()
    {
        $item = new ErrorItem('fatal error');
        $this->assertSame('Error', $item->title);
        $this->assertStringContainsString('fatal error', $item->content);
    }
}
