<?php

use PHPUnit\Framework\TestCase;

final class StrTest extends TestCase
{
    public static function setUpBeforeClass(): void
    {
        require_once 'src/setup.php';
    }

    public function testCapitalize()
    {
        $this->assertSame('Hello World', Str::captalize('hello_world'));
    }

    public function testFilterLinks()
    {
        $this->assertSame(
            'at<a href="http://github.io" target="_blank">http://github.io</a> http',
            Str::filterLinks('athttp://github.io http')
        );
        $this->assertSame(
            'at<a href="https://github.io" target="_blank">https://github.io</a> https',
            Str::filterLinks('athttps://github.io https')
        );
        $this->assertSame(
            'at<a href="ftp://github.io" target="_blank">ftp://github.io</a> ftp',
            Str::filterLinks('atftp://github.io ftp')
        );
    }

    public function testClassToFile()
    {
        $this->assertSame('super_class', Str::classToFile('SuperClass'));
        $this->assertSame('m_s_d_b', Str::classToFile('MSDB'));
    }

    public function testIsValidFileName()
    {
        $this->assertTrue(Str::isValidFileName('abc.txt'));
        $this->assertTrue(Str::isValidFileName('012.jpg'));
        $this->assertFalse(Str::isValidFileName('home/link.txt'));
        $this->assertFalse(Str::isValidFileName('_list.json'));
    }

    public function testBytesXor()
    {
        $this->assertSame("\0", Str::bytesXor('a', 'a'));
        $this->assertSame("\xff", Str::bytesXor("\xff", "\x00"));
        $this->assertSame("\x01", Str::bytesXor("\xff", "\xfe"));
        $this->assertSame("\xfe\x02", Str::bytesXor("\x01\x02", "\xff"));
        $this->assertSame("\x01\x23", Str::bytesXor("\x00", "\x01\x23"));
    }
}
