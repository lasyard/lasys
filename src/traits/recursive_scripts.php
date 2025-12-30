<?php
final class RecursiveScripts extends RecursiveTraits
{
    public function forChild(&$conf, $oldConf)
    {
        if (isset($oldConf[Config::SCRIPTS])) {
            $conf[Config::SCRIPTS] = Arr::uniqueMerge($conf[Config::SCRIPTS] ?? [], $oldConf[Config::SCRIPTS]);
        }
        parent::forChild($conf, $oldConf);
    }
}
