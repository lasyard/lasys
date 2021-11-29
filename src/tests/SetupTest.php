<?php

use PHPUnit\Framework\TestCase;

final class SetupTest extends TestCase
{
    public function test()
    {
        require_once 'src/setup.php';
        $this->assertSame('unknown', SITE);
        $this->assertSame('data', DATA_DIR);
        $this->assertSame('pub', PUB_DIR);
        $this->assertSame('views', VIEWS_DIR);
        $this->assertSame('actions', ACTIONS_DIR);
        $this->assertSame('Lasys', APP_TITLE);
        $this->assertStringStartsWith(ROOT_PATH, DATA_PATH);
        $this->assertStringStartsWith(ROOT_PATH, PUB_PATH);
        $this->assertStringStartsWith(ROOT_PATH, VIEWS_PATH);
        $this->assertStringStartsWith(ROOT_PATH, ACTIONS_PATH);
    }
}
