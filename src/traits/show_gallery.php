<?php
final class ShowGallery extends Traits
{
    private $_rPriv;
    private $_wPriv;

    public function __construct($rPriv = null, $wPriv = null)
    {
        $this->_rPriv = $rPriv;
        $this->_wPriv = $wPriv;
    }

    public function forItem($item, $conf)
    {
        $rPriv = $this->_rPriv ? $this->_rPriv : $conf[Config::READ_PRIV];
        $wPriv = $this->_wPriv ? $this->_wPriv : $conf[Config::EDIT_PRIV];
        $item[Server::GET] ??= GalleryActions::get()->priv(...$rPriv);
        $item[Server::AJAX_GET] ??= GalleryActions::ajaxGet()->priv(...$rPriv);
        $item[Server::POST] ??= GalleryActions::post()->priv(...$wPriv);
        $item[Server::AJAX_DELETE] ??= GalleryActions::ajaxDelete()->priv(...$wPriv);
        return $item;
    }
}
