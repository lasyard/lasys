<?php

use PHPUnit\Framework\TestCase;

final class ConfigTest extends TestCase
{
    public static function setUpBeforeClass(): void
    {
        require_once 'src/setup.php';
    }

    public function testDefault()
    {
        $conf = new Config('non-exist');
        $this->assertTrue($conf->recursive);
        $this->assertTrue($conf->listedOnly);
        $this->assertFalse($conf->order);
        $this->assertEmpty($conf->list);
    }

    public function testExcluded()
    {
        $conf = new Config('default');
        $this->assertTrue($conf->excluded('.'));
        $this->assertTrue($conf->excluded('..'));
        $this->assertTrue($conf->excluded('index.html'));
        $this->assertTrue($conf->excluded('_list.json'));
    }
}
