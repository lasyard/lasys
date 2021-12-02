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
        if (
            self::requestMethod() == 'GET'
            && $_GET['raw'] == true
            && strpos($_SERVER['HTTP_REFERER'], $home) === 0
        ) {
            self::rawFile(DATA_PATH . DS . $path);
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
        return $_SERVER['REQUEST_METHOD'];
    }

    public static function rawFile($path)
    {
        if (!is_file($path)) {
            header('HTTP/1.0 404 Not Found');
            exit;
        }
        $type = (new finfo(FILEINFO_MIME))->file($path);
        header('Cache-Control: public, max-age=3456000');
        $mtime = filemtime($path);
        header('ETag: "' . md5($mtime) . '"');
        header('Last-Modified: ' . gmdate(DATE_RFC7231, $mtime));
        header('Content-Type: ' . $type);
        header('Accept-Ranges: none');
        header('Accept-Length: ' . filesize($path));
        header('Content-Disposition: attachment; filename="' . basename($path) . '"');
        header('Content-Transfer-Encoding: binary');
        readfile($path);
    }
}
