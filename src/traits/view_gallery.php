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
    }
}
