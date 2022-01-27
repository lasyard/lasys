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
        $this->assertSame([], $conf->get(Config::TRAITS));
        $this->assertTrue($conf->get(Config::READ_ONLY));
        $this->assertSame('index', $conf->get(Config::DEFAULT_ITEM));
        $this->assertSame([], $conf->get(Config::READ_PRIV));
        $this->assertSame([User::OWNER, User::EDIT], $conf->get(Config::EDIT_PRIV));
        $this->assertEmpty($conf->list());
    }

    public function testExcluded()
    {
        $conf = new Config('default');
        $this->assertTrue($conf->excluded('index.html'));
        $this->assertTrue($conf->excluded('_list.json'));
    }
}
