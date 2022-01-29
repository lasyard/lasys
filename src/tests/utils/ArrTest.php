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
        $arr = ['a' => 1, 'b' => 2, 'c' => 3];
        $this->assertSame(
            ['a' => 1, 'b' => 2],
            Arr::transKeys($arr, 'a', 'b')
        );
        $this->assertSame(
            ['a' => 1],
            Arr::transKeys($arr, 'a', 'd')
        );
    }

    public function testCopyKeys()
    {
        $arr = ['a' => 1, 'b' => 2, 'c' => 3];
        $target = ['a' => 0, 'b' => 1, 'c' => 2];
        Arr::copyKeys($target, $arr, 'a', 'b');
        $this->assertSame(
            ['a' => 1, 'b' => 2, 'c' => 2],
            $target
        );
        $target = ['a' => 0, 'b' => 1, 'c' => 2];
        Arr::copyKeys($target, $arr, 'a', 'd');
        $this->assertSame(
            ['a' => 1, 'b' => 1, 'c' => 2],
            $target
        );
    }

    public function testCopyNonExistingKeys()
    {
        $arr = ['a' => 1, 'b' => 2, 'c' => 3];
        $target = ['a' => 0, 'c' => 2];
        Arr::copyNonExistingKeys($target, $arr, 'a', 'b', 'c');
        $this->assertSame(0, $target['a']);
        $this->assertSame(2, $target['b']);
        $this->assertSame(2, $target['c']);
    }

    public function testToArray()
    {
        Arr::toArray($obj);
        $this->assertSame([], $obj);
        $obj['a'] = null;
        Arr::toArray($obj['a']);
        $this->assertSame([], $obj['a']);
        Arr::toArray($obj['b']);
        $this->assertSame([], $obj['b']);
        Arr::toArray($obj['b']['c']);
        $this->assertSame([], $obj['b']['c']);
        $obj = 'hello';
        Arr::toArray($obj);
        $this->assertSame(['hello'], $obj);
    }
}
