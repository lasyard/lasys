<?php

use PHPUnit\Framework\TestCase;

final class ArrTest extends TestCase
{
    public static function setUpBeforeClass(): void
    {
        require_once 'src/setup.php';
    }

    public function testTransKeys()
    {
        $arr = ['a' => 1, 'b' => '2', 'c' => 3];
        $this->assertSame(
            ['a' => 1, 'b' => '2'],
            Arr::transKeys($arr, 'a', 'b')
        );
        $this->assertSame(
            ['a' => 1],
            Arr::transKeys($arr, 'a', 'd')
        );
    }
}
