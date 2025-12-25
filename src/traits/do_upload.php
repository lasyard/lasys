<?php
class DoUpload extends RecursiveTraits
{
    public function forSelf(&$conf, $oldConf)
    {
        Arr::copyNonExistingKeys(
            $conf,
            $oldConf,
            FileActions::UPLOAD_TITLE,
            FileActions::ACCEPT,
            FileActions::SIZE_LIMIT,
            Config::ORDER,
        );
        $upload = &$conf[Config::LIST][FileActions::UPLOAD_ITEM];
        $upload[Config::TITLE] ??= $conf[FileActions::UPLOAD_TITLE]
            ?? FileActions::DEFAULT[FileActions::UPLOAD_TITLE];
        $upload[Config::BUTTON] ??= Icon::UPLOAD;
        $upload[Server::GET] ??= FileActions::uploadForm()->priv(...$conf[Config::PRIV_POST]);
        $upload[Server::POST] ??= FileActions::post()->priv(...$conf[Config::PRIV_POST]);
        $conf[Config::ETC][Server::UPDATE] ??= FileActions::update();
        $conf[Config::ETC][Server::AJAX_DELETE] ??= FileActions::ajaxDelete();
    }
}
