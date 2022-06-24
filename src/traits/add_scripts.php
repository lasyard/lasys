<?php
final class AddScripts extends Traits
{
    private $_scripts;

    public function __construct(...$scripts)
    {
        $this->_scripts = $scripts;
    }

    public function forSelf(&$conf, $oldConf)
    {
        $conf[Config::ETC][Config::SCRIPTS] = Arr::uniqueMerge($conf[Config::ETC][Config::SCRIPTS], $this->_scripts);
    }

    public function forEachItem(&$item, $conf)
    {
        $this->addTo($item);
    }

    public function forItem(&$item, $conf)
    {
        $item[Config::SCRIPTS] = Arr::uniqueMerge($item[Config::SCRIPTS], $this->_scripts);
    }

    public function forChild(&$conf, $oldConf)
    {
        $this->addTo($conf);
    }
}
