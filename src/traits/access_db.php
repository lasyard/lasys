<?php
final class AccessDb extends Traits
{
    public function forEachItem(&$item, $conf)
    {
        $this->addTo($item);
        $item[DbActions::LABELS] ??= $conf[DbActions::LABELS] ?? null;
        $item[DbActions::SCRIPT] = Arr::uniqueMerge($conf[DbActions::SCRIPT], $item[DbActions::SCRIPT]);
    }

    public function forItem(&$item, $conf)
    {
        $rPriv = $item[Config::PRIV_READ] ?? $conf[Config::PRIV_READ];
        $wPriv = $item[Config::PRIV_EDIT] ?? $conf[Config::PRIV_EDIT];
        $item[Server::GET] ??= DbActions::get()->priv(...$rPriv);
        $item[Server::AJAX_GET] ??= DbActions::ajaxGet()->priv(...$rPriv);
        $item[Server::AJAX_UPDATE] ??= DbActions::ajaxUpdate()->priv(...$wPriv);
        $item[Server::AJAX_POST] ??= DbActions::ajaxPost()->priv(...$wPriv);
        $item[Server::AJAX_DELETE] ??= DbActions::ajaxDelete()->priv(...$wPriv);
    }
}
