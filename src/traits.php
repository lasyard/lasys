<?php
abstract class Traits
{
    private static $_cache = [];

    protected function __construct()
    {
    }

    public function forSelf($conf, $oldConf)
    {
        return $conf;
    }

    public function forChild($conf, $oldConf)
    {
        return $conf;
    }

    public function forItem($item, $conf)
    {
        return $item;
    }

    public static function __callStatic($method, $args)
    {
        $class = ucfirst($method);
        require_once 'traits' . DS . Str::classToFile($class) . '.php';
        if (count($args) == 0) {
            if (!isset(self::$_cache[$method])) {
                self::$_cache[$method] = new $class(...$args);
            }
            return self::$_cache[$method];
        }
        return new $class(...$args);
    }
}
