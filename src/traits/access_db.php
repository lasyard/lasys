<?php
final class AccessDb extends Traits
{
    public function forEachItem(&$item, $conf)
    {
        $this->addTo($item);
    }

    public function forItem(&$item, $conf)
    {
        $item[Server::GET] ??= DbActions::get();
        $item[Server::AJAX_GET] ??= DbActions::ajaxGet();
        $item[Server::AJAX_UPDATE] ??= DbActions::ajaxUpdate();
        $item[Server::AJAX_POST] ??= DbActions::ajaxPost();
        $item[Server::AJAX_DELETE] ??= DbActions::ajaxDelete();
    }
}
