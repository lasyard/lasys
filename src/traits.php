<?php
abstract class Traits
{
    private static $_doUpload = null;
    private static $_passDownDefaultPage = null;

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

    public function forItem($item, $conf)
    {
        return $item;
    }

    public static function accessDb($rPriv = null, $wPriv = null)
    {
        require_once 'traits' . DS . 'access_db.php';
        return new AccessDb($rPriv, $wPriv);
    }

    public static function doUpload($recursive = true)
    {
        if (self::$_doUpload === null) {
            require_once 'traits' . DS . 'do_upload.php';
            self::$_doUpload = new DoUpload();
        }
        return self::$_doUpload;
    }

    public static function passDownDefaultPage()
    {
        if (self::$_passDownDefaultPage === null) {
            require_once 'traits' . DS . 'pass_down_default_page.php';
            self::$_passDownDefaultPage = new PassDownDefaultPage();
        }
        return self::$_passDownDefaultPage;
    }
}
