<?php
require_once 'app.php';

final class Sys
{
    private static $_app;

    private function __construct()
    {
    }

    public static function app()
    {
        self::$_app = self::$_app ?? new App;
        return self::$_app;
    }
}
