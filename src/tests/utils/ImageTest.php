<?php

use PHPUnit\Framework\TestCase;

final class ImageTest extends TestCase
{
    public static function setUpBeforeClass(): void
    {
        require_once 'src/setup.php';
    }

    public function testGetExifDate()
    {
        $time = Image::getExifDate(__DIR__ . DS . 'pigs.jpg');
        $this->assertSame('2002-06-01 00:00:00', date('Y-m-d H:i:s', $time));
    }
}
