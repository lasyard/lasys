<?php
abstract class RecursiveTraits extends Traits
{
    public function forChild(&$conf, $oldConf)
    {
        $this->addTo($conf);
    }
}
