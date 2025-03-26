<?php
final class Msg
{
    private function __construct() {}

    public static function infoHtml($msg)
    {
        return '<p class="center">' . Icon::INFO . ' ' . $msg . '</p>';
    }

    public static function info($msg)
    {
        echo self::infoHtml($msg);
    }

    public static function warnHtml($msg)
    {
        return '<p class="hot center">' . Icon::WARN . ' ' . $msg . '</p>';
    }

    public static function warn($msg)
    {
        echo self::warnHtml($msg);
    }
}
