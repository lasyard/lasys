<?php
final class DirOperations extends RecursiveTraits
{
    public function forSelf(&$conf, $oldConf)
    {
        $conf[Config::LIST][FileActions::DIR_ITEM] = [
            Config::TYPE => Config::BTN,
            Config::ICON => Icon::FOLDER,
            Config::PRIV_READ => User::ADMIN,
            Config::PRIV_POST => User::ADMIN,
            Config::PRIV_EDIT => User::ADMIN,
            Server::GET => FileActions::infoChange(),
            Server::POST => FileActions::infoPost(),
        ];
    }
}
