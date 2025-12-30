<?php
final class RecursiveDefaultPage extends RecursiveTraits
{
    public function forChild(&$conf, $oldConf)
    {
        if (
            !isset($conf[Config::LIST][$conf[Config::DEFAULT_ITEM]][Server::GET])
            && isset($oldConf[Config::LIST][$oldConf[Config::DEFAULT_ITEM]])
        ) {
            Arr::copyKeys(
                $conf[Config::LIST][$conf[Config::DEFAULT_ITEM]],
                $oldConf[Config::LIST][$oldConf[Config::DEFAULT_ITEM]],
                Server::GET,
                Config::TYPE,
                Config::HIDDEN,
            );
        }
        parent::forChild($conf, $oldConf);
    }
}
