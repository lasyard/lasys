<?php

use PHPUnit\Framework\TestCase;

final class SetupTest extends TestCase
{
    public function test()
    {
        require_once 'src/setup.php';
        $this->assertIsString(DATA_DIR);
        $this->assertIsString(PUB_DIR);
        $this->assertIsString(VIEWS_DIR);
        $this->assertIsString(APP_TITLE);
        $this->assertStringStartsWith(ROOT_PATH, DATA_PATH);
        $this->assertStringStartsWith(ROOT_PATH, PUB_PATH);
        $this->assertStringStartsWith(ROOT_PATH, VIEWS_PATH);
    }
}
