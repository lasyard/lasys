<?php
final class RecursiveStyles extends RecursiveTraits
{
    public function forChild(&$conf, $oldConf)
    {
        if (isset($oldConf[Config::STYLES])) {
            $conf[Config::STYLES] = Arr::uniqueMerge($conf[Config::STYLES] ?? [], $oldConf[Config::STYLES]);
        }
        parent::forChild($conf, $oldConf);
    }
}
