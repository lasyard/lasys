<?php
final class AccessDb extends Traits
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
        $item[Server::GET] ??= DbActions::get()->priv(...$rPriv);
        $item[Server::AJAX_GET] ??= DbActions::ajaxGet()->priv(...$rPriv);
        $item[Server::AJAX_PUT] ??= DbActions::ajaxPut()->priv(...$wPriv);
        $item[Server::POST_UPDATE] ??= DbActions::postUpdate()->priv(...$wPriv);
        $item[Server::AJAX_POST] ??= DbActions::ajaxPost()->priv(...$wPriv);
        $item[Server::AJAX_DELETE] ??= DbActions::ajaxDelete()->priv(...$wPriv);
        return $item;
    }
}
