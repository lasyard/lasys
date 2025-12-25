<?php
final class ViewFiles extends RecursiveTraits
{
    public function forSelf(&$conf, $oldConf)
    {
        $conf[Config::ETC][Server::GET] ??= FileActions::get();
    }
}
