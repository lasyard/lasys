<?php

use PHPUnit\Framework\TestCase;

final class ServerTest extends TestCase
{
    public static function setUpBeforeClass(): void
    {
        require_once 'src/setup.php';
    }

    public function testGetHomeAndPath()
    {
        $_SERVER = [
            'HTTP_HOST' => 'www.lasys.org',
            'SERVER_PORT' => 80,
            'REQUEST_URI' => '/workspace/',
            'PHP_SELF' => '/workspace/index.php',
            'REQUEST_METHOD' => 'GET',
        ];
        list($base, $path) = Server::getHomeAndPath();
        $this->assertSame('http://www.lasys.org/workspace/', $base);
        $this->assertSame([''], $path);
    }

    public function testGetHomeAndPath1()
    {
        $_SERVER = [
            'HTTP_HOST' => 'www.lasys.org',
            'SERVER_PORT' => 80,
            'REQUEST_URI' => '/workspace/a/b/c?name=test',
            'PHP_SELF' => '/workspace/a/index.php',
            'REQUEST_METHOD' => 'GET',
        ];
        list($base, $path) = Server::getHomeAndPath();
        $this->assertSame('http://www.lasys.org/workspace/a/', $base);
        $this->assertSame(['b', 'c'], $path);
    }

    public function testGetHomeAndPath2()
    {
        $_SERVER = [
            'HTTP_HOST' => 'www.lasys.org',
            'HTTPS' => 'on',
            'SERVER_PORT' => 80,
            'REQUEST_URI' => '/workspace/a/b/c?name=test',
            'PHP_SELF' => '/workspace/a/index.php',
            'REQUEST_METHOD' => 'GET',
        ];
        list($base, $path) = Server::getHomeAndPath();
        $this->assertSame('https://www.lasys.org:80/workspace/a/', $base);
        $this->assertSame(['b', 'c'], $path);
    }
}
