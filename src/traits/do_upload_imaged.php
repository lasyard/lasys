<?php
require_once 'do_upload.php';

final class DoUploadImaged extends DoUpload
{
    public function forSelf(&$conf, $oldConf)
    {
        parent::forSelf($conf, $oldConf);
        $conf[Config::LIST][FileActions::IMAGES_ITEM] = [
            Config::TYPE => Config::BTN,
            Config::TRAITS => [Traits::viewGallery()],
            FileActions::UPLOAD_TITLE => 'Attachment Image',
            FileActions::ACCEPT => 'image/*',
            GalleryActions::THUMB_SIZE => -1,
            GalleryActions::KEEP_NAME => true,
            Config::ICON => Icon::IMAGES,
            Config::PRIV_READ => $conf[Config::PRIV_POST],
        ];
    }
}
