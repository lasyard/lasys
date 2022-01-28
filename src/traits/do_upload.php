<?php
final class DoUpload extends Traits
{
    public function forSelf($conf, $oldConf)
    {
        Arr::copyNonExistingKeys(
            $conf,
            $oldConf,
            FileActions::UPLOAD_TITLE,
            FileActions::ACCEPT,
            FileActions::SIZE_LIMIT,
            FileActions::ORDER,
        );
        if (isset($conf[Config::READ_ONLY]) && $conf[Config::READ_ONLY]) {
            return $conf;
        }
        $conf[Config::LIST][FileActions::UPLOAD_ITEM] = [
            Config::TITLE => $conf[FileActions::UPLOAD_TITLE] ?? FileActions::DEFAULT[FileActions::UPLOAD_TITLE],
            Config::BUTTON => Icon::UPLOAD,
            Server::GET => FileActions::uploadForm()->priv(User::EDIT),
            Server::POST => FileActions::post()->priv(User::EDIT),
        ];
        $conf[Config::ETC][Server::UPDATE] ??= FileActions::update()->priv(...$conf[Config::EDIT_PRIV]);
        $conf[Config::ETC][Server::AJAX_DELETE] ??= FileActions::ajaxDelete()->priv(...$conf[Config::EDIT_PRIV]);
        return $conf;
    }

    public function forChild($conf, $oldConf)
    {
        $conf[Config::TRAITS][] = $this;
        return $conf;
    }
}
