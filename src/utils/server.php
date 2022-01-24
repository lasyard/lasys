<?php
final class Server
{
    public const TYPE_KEY = 'requestType';
    public const INVALID = 'invalid';
    public const HEAD = 'head';
    public const GET = 'get';
    public const GET_RAW = 'raw';
    public const AJAX_GET = 'ajaxGet';
    public const POST = 'post';
    public const AJAX_POST = 'ajaxPost';
    public const UPDATE = 'update';
    public const AJAX_UPDATE = 'ajaxUpdate';
    public const DELETE = 'delete';
    public const AJAX_DELETE = 'ajaxDelete';

    public const QUERY_GET_RAW = self::TYPE_KEY . '=' . self::GET_RAW;
    public const QUERY_UPDATE = self::TYPE_KEY . '=' . self::UPDATE;
    public const QUERY_DELETE = self::TYPE_KEY . '=' . self::DELETE;

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
        $home = $protocol . '://' . $host;
        // Seems that `$_SERVER['HTTP_HOST']` contains the port part of url.
        // $port = $_SERVER['SERVER_PORT'];
        // if ($protocol == 'http' && $port != 80 || $protocol == 'https' && $port != 443) {
        //     $home .= ":$port";
        // }
        $path = preg_replace('/\?.*$/', '', $_SERVER['REQUEST_URI']);
        $prefix = dirname($_SERVER['PHP_SELF']);
        if (substr($prefix, -1) != '/') {
            $prefix .= '/';
        }
        if (strpos($path, $prefix) === 0) {
            $path = substr_replace($path, '', 0, strlen($prefix));
            $home .= $prefix;
        }
        $type = self::requestType();
        if ($type == self::GET_RAW) {
            if (strpos($_SERVER['HTTP_REFERER'], $home) === 0) {
                self::rawFile(DATA_PATH . DS . $path);
            }
            exit;
        }
        return [$home, explode('/', $path), $type];
    }

    public static function isAjax($type)
    {
        return in_array($type, [self::AJAX_GET, self::AJAX_POST, self::AJAX_UPDATE, self::AJAX_DELETE]);
    }

    public static function isEdit($type)
    {
        return !in_array($type, [self::GET, self::AJAX_GET]);
    }

    private static function isAjaxRequest()
    {
        return isset($_SERVER['HTTP_X_REQUESTED_WITH'])
            && strcasecmp($_SERVER['HTTP_X_REQUESTED_WITH'], 'xmlhttprequest') == 0;
    }

    private static function requestType()
    {
        // Form method can only be `GET` or `POST`.
        // Some hosting services don't allow methods other than `GET` and `POST`.
        switch ($_SERVER['REQUEST_METHOD']) {
            case 'GET':
                if (isset($_GET[self::TYPE_KEY]) && $_GET[self::TYPE_KEY] == self::GET_RAW) {
                    return self::GET_RAW;
                }
                return self::isAjaxRequest() ? self::AJAX_GET : self::GET;
            case 'POST':
                if (isset($_GET[self::TYPE_KEY])) {
                    switch ($_GET[self::TYPE_KEY]) {
                        case self::UPDATE:
                            return self::isAjaxRequest() ? self::AJAX_UPDATE : self::UPDATE;
                        case self::DELETE:
                            return self::isAjaxRequest() ? self::AJAX_DELETE : self::DELETE;
                        default:
                            return  self::INVALID;
                    }
                }
                return self::isAjaxRequest() ? self::AJAX_POST : self::POST;
            case 'PUT':
                return self::isAjaxRequest() ? self::AJAX_UPDATE : self::UPDATE;
            case 'DELETE':
                return self::isAjaxRequest() ? self::AJAX_DELETE : self::DELETE;
            case 'HEAD':
                return self::HEAD;
            default:
        }
        return self::INVALID;
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
        exit;
    }

    public static function rawUrl($url)
    {
        // ! absolute, protocol, inline, javascript
        if (strpos($url, ':') === false and !preg_match('/^\//', $url)) {
            return $url . '?' . self::QUERY_GET_RAW;
        }
        return $url;
    }
}
