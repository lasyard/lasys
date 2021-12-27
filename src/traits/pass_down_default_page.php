<?php
final class PassDownDefaultPage extends Traits
{
    private static $instance = null;

    public static function get()
    {
        self::$instance = self::$instance ?? new PassDownDefaultPage();
        return self::$instance;
    }

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
            $conf[Config::TRAITS][] = self::$instance;
        }
        return $conf;
    }
}
