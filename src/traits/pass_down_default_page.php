<?php
final class PassDownDefaultPage extends RecursiveTraits
{
    public function forChild(&$conf, $oldConf)
    {
        if (!isset($conf[Config::LIST][$conf[Config::DEFAULT_ITEM]][Server::GET])) {
            Arr::copyKeys(
                $conf[Config::LIST][$conf[Config::DEFAULT_ITEM]],
                $oldConf[Config::LIST][$oldConf[Config::DEFAULT_ITEM]],
                Server::GET,
                Config::HIDDEN,
                Config::TITLE,
            );
        }
        parent::forChild($conf, $oldConf);
    }
}
