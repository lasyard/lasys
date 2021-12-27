<?php
abstract class Traits
{
    protected function __construct()
    {
    }

    public function forSelf($conf, $oldConf)
    {
        return $conf;
    }

    public function forChild($conf, $oldConf)
    {
        return $conf;
    }

    public static function passDownDefaultPage()
    {
        require_once 'traits' . DS . 'pass_down_default_page.php';
        return PassDownDefaultPage::get();
    }

    public static function doUpload($recursive = true)
    {
        require_once 'traits' . DS . 'do_upload.php';
        return DoUpload::get();
    }
}
