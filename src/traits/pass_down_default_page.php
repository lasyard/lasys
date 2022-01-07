<?php
final class PassDownDefaultPage extends Traits
{
    public function forChild($conf, $oldConf)
    {
        if (!isset($conf[Config::LIST][$conf[Config::DEFAULT_ITEM]][Server::GET])) {
            Arr::copyKeys(
                $conf[Config::LIST][$conf[Config::DEFAULT_ITEM]],
                $oldConf[Config::LIST][$conf[Config::DEFAULT_ITEM]],
                Server::GET,
                Config::HIDDEN,
                Config::TITLE,
            );
            $conf[Config::TRAITS][] = $this;
        }
        return $conf;
    }
}
