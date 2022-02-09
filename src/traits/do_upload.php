<?php
final class DoUpload extends Traits
{
    public function forSelf(&$conf, $oldConf)
    {
        if (!$conf[Config::READ_ONLY]) {
            $upload = &$conf[Config::LIST][FileActions::UPLOAD_ITEM];
            $upload[Config::TITLE] ??= $conf[FileActions::UPLOAD_TITLE]
                ?? FileActions::DEFAULT[FileActions::UPLOAD_TITLE];
            $upload[Config::BUTTON] ??= Icon::UPLOAD;
            $upload[Server::GET] ??= FileActions::uploadForm()->priv(...$conf[Config::PRIV_POST]);
            $upload[Server::POST] ??= FileActions::post()->priv(...$conf[Config::PRIV_POST]);
            $conf[Config::ETC][Server::UPDATE] ??= FileActions::update()->priv(...$conf[Config::PRIV_EDIT]);
            $conf[Config::ETC][Server::AJAX_DELETE] ??= FileActions::ajaxDelete()->priv(...$conf[Config::PRIV_EDIT]);
        }
    }

    public function forEachItem(&$item, $conf)
    {
        $this->addTo($item);
    }

    public function forChild(&$conf, $oldConf)
    {
        Arr::copyNonExistingKeys(
            $conf,
            $oldConf,
            FileActions::UPLOAD_TITLE,
            FileActions::ACCEPT,
            FileActions::SIZE_LIMIT,
            Config::ORDER,
        );
        $this->addTo($conf);
    }
}
