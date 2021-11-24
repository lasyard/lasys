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
}
