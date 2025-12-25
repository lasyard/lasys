<?php
abstract class Traits
{
    private static $_cache = [];

    protected function __construct() {}

    protected function addTo(&$target)
    {
        Arr::makeArray($target[Config::TRAITS]);
        foreach ($target[Config::TRAITS] as $t) {
            if (get_class($t) == get_class($this)) {
                return;
            }
        }
        $target[Config::TRAITS][] = $this;
    }

    public static function __callStatic($method, $args)
    {
        $class = ucfirst($method);
        require_once 'traits' . DS . Str::classToFile($class) . '.php';
        if (count($args) == 0) {
            if (!isset(self::$_cache[$method])) {
                self::$_cache[$method] = new $class();
            }
            return self::$_cache[$method];
        }
        return new $class(...$args);
    }

    // apply to the new conf if traits is configured on the old conf
    public function forChild(&$conf, $oldConf) {}

    // apply to the conf if traints is configured on the item which the conf is created from
    public function forMe(&$conf, $oldConf) {}

    // apply to the conf if traits is configured in the conf
    public function forSelf(&$conf, $oldConf) {}

    // apply to each item if traits is configured in the conf
    public function forEachItem(&$item, $conf) {}

    // apply to the item if traits is configured on the item
    public function forItem(&$item, $conf) {}
}
