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
        $conf = Config::root('non-exist');
        $this->assertSame([], $conf->get(Config::TRAITS));
        $this->assertTrue($conf->get(Config::READ_ONLY));
        $this->assertSame('index', $conf->get(Config::DEFAULT_ITEM));
        $this->assertSame([], $conf->get(Config::PRIV_READ));
        $this->assertSame([User::OWNER, User::EDIT], $conf->get(Config::PRIV_EDIT));
        $this->assertSame([User::EDIT], $conf->get(Config::PRIV_POST));
        $this->assertEmpty($conf->list());
    }

    public function testExcluded()
    {
        $conf = Config::root('non-exist');
        $this->assertTrue($conf->excluded('_list.json'));
    }
}
