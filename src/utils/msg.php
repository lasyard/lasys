<?php
final class Msg
{
    private function __construct()
    {
    }

    public static function info($msg)
    {
        echo '<p class="center">', Icon::INFO, ' ', $msg, '</p>';
    }

    public static function warn($msg)
    {
        echo '<p class="hot center">', Icon::WARN, ' ', $msg, '</p>';
    }
}
