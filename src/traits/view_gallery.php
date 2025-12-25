<?php
final class ViewGallery extends Traits
{
    private $_wPriv;

    public function forMe(&$conf, $oldConf)
    {
        $conf[Config::ETC][Server::AJAX_DELETE] ??= GalleryActions::ajaxDelete()->priv(...$this->_wPriv);
        $conf[Config::ETC][Server::AJAX_UPDATE] ??= GalleryActions::ajaxUpdate()->priv(...$this->_wPriv);
    }

    public function forEachItem(&$item, $conf)
    {
        $this->addTo($item);
    }

    public function forItem(&$item, $conf)
    {
        $wPriv = $item[Config::PRIV_EDIT] ?? $conf[Config::PRIV_EDIT];
        $item[Server::GET] ??= GalleryActions::get();
        $item[Server::AJAX_GET] ??= GalleryActions::ajaxGet();
        $item[Server::POST] ??= GalleryActions::post();
        // Set this to pass down ajax delete & update.
        $item[Server::AJAX_DELETE] ??= Actions::dir(Config::PRIV_EDIT);
        $item[Server::AJAX_UPDATE] ??= Actions::dir(Config::PRIV_EDIT);
        $item['check'] = GalleryActions::check()->priv(User::ADMIN);
        $this->_wPriv = $wPriv;
    }
}
