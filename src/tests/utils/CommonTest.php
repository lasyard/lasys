<?php

use PHPUnit\Framework\TestCase;

final class CommonTest extends TestCase
{
    public static function setUpBeforeClass(): void
    {
        require_once 'src/setup.php';
    }

    public static function funTestee1($msg)
    {
        return 'I am testee 1. msg = ' . $msg;
    }

    public static function funTestee2($msg)
    {
        echo 'I am testee 1. msg = ' . $msg;
    }

    public function testGetOutput1()
    {
        $this->assertSame(
            'I am testee 1. msg = abc',
            Common::getOutput(['CommonTest', 'funTestee1'], ['abc'])
        );
    }

    public function testGetOutput2()
    {
        $this->assertSame(
            'I am testee 1. msg = abc',
            Common::getOutput(['CommonTest', 'funTestee2'], ['abc'])
        );
    }
}
