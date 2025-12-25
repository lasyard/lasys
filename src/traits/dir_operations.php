<?php
final class DirOperations extends RecursiveTraits
{
    public function forSelf(&$conf, $oldConf)
    {
        $conf[Config::LIST][FileActions::DIR_ITEM] = [
            Config::BUTTON => Icon::FOLDER,
            Config::PRIV_READ => [User::ADMIN],
            Server::GET => FileActions::infoChange(),
            Server::POST => FileActions::infoPost(),
        ];
    }
}
