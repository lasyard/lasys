<?php
final class ViewFiles extends Traits
{
    public function forSelf(&$conf, $oldConf)
    {
        $conf[Config::ETC][Server::GET] ??= FileActions::get();
    }

    public function forEachItem(&$item, $conf)
    {
        $this->addTo($item);
    }

    public function forChild(&$conf, $oldConf)
    {
        $this->addTo($conf);
    }
}
