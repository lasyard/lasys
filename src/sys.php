<?php
require_once 'app.php';

final class Sys
{
    private static $_app;
    private static $_db;
    private static $_user;

    private function __construct()
    {
    }

    public static function app()
    {
        self::$_app = self::$_app ?? new App;
        return self::$_app;
    }

    public static function db()
    {
        self::$_db = self::$_db ?? new Db;
        return self::$_db;
    }

    public static function user()
    {
        self::$_user = self::$_user ?? new User();
        return self::$_user;
    }
}
