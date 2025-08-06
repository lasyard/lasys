<?php
// For PHP version < 8
if (!function_exists('str_starts_with')) {
    function str_starts_with($haystack, $needle)
    {
        return strpos($haystack, $needle) === 0;
    }
}
if (!function_exists('str_contains')) {
    function str_contains($haystack, $needle)
    {
        return strpos($haystack, $needle) !== false;
    }
}
if (!function_exists('str_ends_with')) {
    function str_ends_with($haystack, $needle)
    {
        if ('' === $haystack && '' !== $needle) {
            return false;
        }
        $len = strlen($needle);
        return 0 === substr_compare($haystack, $needle, -$len, $len);
    }
}

define('DS', DIRECTORY_SEPARATOR);
if (!defined('ASSET_SUM')) {
    define('ASSET_SUM', '0');
}
if (!defined('SITE')) {
    define('SITE', 'unknown');
}
if (!defined('CONF_DIR')) {
    define('CONF_DIR', 'configs');
}
if (!defined('DATA_DIR')) {
    define('DATA_DIR', 'data');
}
if (!defined('PUB_DIR')) {
    define('PUB_DIR', 'pub');
}
if (!defined('ACTIONS_DIR')) {
    define('ACTIONS_DIR', 'actions');
}
if (!defined('VIEWS_DIR')) {
    define('VIEWS_DIR', 'views');
}
if (!defined('UTILS_DIR')) {
    define('UTILS_DIR', 'utils');
}
if (!defined('APP_TITLE')) {
    define('APP_TITLE', 'Lasys');
}
if (!defined('ROOT_PATH')) {
    define('ROOT_PATH', dirname(__DIR__));
}
define('CONF_PATH', ROOT_PATH . DS . CONF_DIR);
define('DATA_PATH', ROOT_PATH . DS . DATA_DIR);
define('PUB_PATH', ROOT_PATH . DS . PUB_DIR);
define('ACTIONS_PATH', ROOT_PATH . DS . ACTIONS_DIR);
define('VIEWS_PATH', ROOT_PATH . DS . VIEWS_DIR);
define('UTILS_PATH', ROOT_PATH . DS . UTILS_DIR);
set_include_path(
    get_include_path()
        . PATH_SEPARATOR . dirname(__DIR__) . DS . 'vendor'
        . PATH_SEPARATOR . __DIR__
        . PATH_SEPARATOR .  ACTIONS_PATH
        . PATH_SEPARATOR . __DIR__ . DS . 'actions'
        . PATH_SEPARATOR . __DIR__ . DS . 'parsers'
        . PATH_SEPARATOR . __DIR__ . DS . 'utils'
        . PATH_SEPARATOR . UTILS_PATH
);
// Load it for autoload has not been enabled.
require_once 'str.php';
spl_autoload_register(function ($class) {
    $file = Str::classToFile($class) . '.php';
    require_once $file;
});
define('COMMON_SCRIPTS', [
    'js' . DS . 'main',
]);
define('COMMON_STYLES', [
    'css' . DS . 'main',
    'https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css',
]);
