<?php
final class Server
{
    private function __construct()
    {
    }

    public static function getHomeAndPath()
    {
        if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
            $protocol = 'https';
        } else {
            $protocol = 'http';
        }
        $host = $_SERVER['HTTP_HOST'];
        $port = $_SERVER['SERVER_PORT'];
        $home = $protocol . '://' . $host;
        if ($protocol == 'http' && $port != 80 || $protocol == 'https' && $port != 443) {
            $home .= ":$port";
        }
        $path = preg_replace('/\?.*$/', '', $_SERVER['REQUEST_URI']);
        $prefix = dirname($_SERVER['PHP_SELF']);
        if (substr($prefix, -1) != '/') {
            $prefix .= '/';
        }
        if (strpos($path, $prefix) === 0) {
            $path = substr_replace($path, '', 0, strlen($prefix));
            $home .= $prefix;
        }
        return [$home, explode('/', $path)];
    }

    public static function isAjaxRequest()
    {
        return isset($_SERVER['HTTP_X_REQUESTED_WITH'])
            && strcasecmp($_SERVER['HTTP_X_REQUESTED_WITH'], 'xmlhttprequest') == 0;
    }

    public static function requestMethod()
    {
        return getenv('REQUEST_METHOD');
    }
}
