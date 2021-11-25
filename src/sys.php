<?php
require_once 'app.php';

final class Sys
{
    public const NO_CACHE_HEADERS = [
        'Cache-Control: no-cache, no-store, must-revalidate',
        'Expires: 0',
    ];

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
