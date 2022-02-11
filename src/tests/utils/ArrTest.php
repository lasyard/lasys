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

    public function testMakeArray()
    {
        Arr::makeArray($obj);
        $this->assertSame([], $obj);
        $obj['a'] = null;
        Arr::makeArray($obj['a']);
        $this->assertSame([], $obj['a']);
        Arr::makeArray($obj['b']);
        $this->assertSame([], $obj['b']);
        Arr::makeArray($obj['b']['c']);
        $this->assertSame([], $obj['b']['c']);
        $obj = 'hello';
        Arr::makeArray($obj);
        $this->assertSame(['hello'], $obj);
    }

    public function testForOneOrMany()
    {
        $obj = 10;
        Arr::forOneOrMany($obj, function (&$o) {
            $o = $o * 2;
        });
        $this->assertSame(20, $obj);
        $objs = [1, 2, 3];
        Arr::forOneOrMany($objs, function (&$o) {
            $o = $o * 2;
        });
        $this->assertSame([2, 4, 6], $objs);
    }
}
