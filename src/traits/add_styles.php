<?php
final class AddStyles extends Traits
{
    private $_styles;

    public function __construct(...$styles)
    {
        $this->_styles = $styles;
    }

    public function forSelf(&$conf, $oldConf)
    {
        $conf[Config::ETC][Config::STYLES] = Arr::uniqueMerge($conf[Config::ETC][Config::STYLES], $this->_styles);
    }

    public function forEachItem(&$item, $conf)
    {
        $this->addTo($item);
    }

    public function forItem(&$item, $conf)
    {
        $item[Config::STYLES] = Arr::uniqueMerge($item[Config::STYLES], $this->_styles);
    }

    public function forChild(&$conf, $oldConf)
    {
        $this->addTo($conf);
    }
}
