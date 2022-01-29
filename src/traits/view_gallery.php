<?php
final class ViewGallery extends Traits
{
    private $_wPriv;

    public function forChild(&$conf, $oldConf)
    {
        $conf[Config::READ_ONLY] = false;
        $conf[Config::ETC][Server::AJAX_DELETE] ??= GalleryActions::ajaxDelete()->priv(...$this->_wPriv);
    }

    public function forItem(&$item, $conf)
    {
        $rPriv = $item[Config::PRIV_READ] ?? $conf[Config::PRIV_READ];
        $wPriv = $item[Config::PRIV_EDIT] ?? $conf[Config::PRIV_EDIT];
        $pPriv = $item[Config::PRIV_POST] ?? $conf[Config::PRIV_POST];
        $item[Server::GET] ??= GalleryActions::get()->priv(...$rPriv);
        $item[Server::AJAX_GET] ??= GalleryActions::ajaxGet()->priv(...$rPriv);
        $item[Server::POST] ??= GalleryActions::post()->priv(...$pPriv);
        // Set this to pass down ajax delete.
        $item[Server::AJAX_DELETE] ??= Actions::noop();
        $this->_wPriv = $wPriv;
    }
}
