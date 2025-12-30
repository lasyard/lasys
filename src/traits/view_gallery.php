<?php
final class ViewGallery extends Traits
{
    public function forMe(&$conf, $oldConf)
    {
        $conf[Config::TRAITS] = [];
        $conf[Config::ETC][Server::AJAX_DELETE] ??= GalleryActions::ajaxDelete();
        $conf[Config::ETC][Server::AJAX_UPDATE] ??= GalleryActions::ajaxUpdate();
    }

    public function forEachItem(&$item, $conf)
    {
        $this->addTo($item);
    }

    public function forItem(&$item, $conf)
    {
        $item[Server::GET] ??= GalleryActions::get();
        $item[Server::AJAX_GET] ??= GalleryActions::ajaxGet();
        $item[Server::POST] ??= GalleryActions::post();
        // Set this to pass down ajax delete & update.
        $item[Server::AJAX_DELETE] ??= Actions::dir(Config::PRIV_EDIT);
        $item[Server::AJAX_UPDATE] ??= Actions::dir(Config::PRIV_EDIT);
    }
}
