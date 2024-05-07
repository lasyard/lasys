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
if (!defined('VERSION')) {
    define('VERSION', '0');
}
if (!defined('SITE')) {
    define('SITE', 'unknown');
}
if (!defined('DATA_DIR')) {
    define('DATA_DIR', 'data');
}
if (!defined('PUB_DIR')) {
    define('PUB_DIR', 'pub');
}
if (!defined('VIEWS_DIR')) {
    define('VIEWS_DIR', 'views');
}
if (!defined('ACTIONS_DIR')) {
    define('ACTIONS_DIR', 'actions');
}
if (!defined('APP_TITLE')) {
    define('APP_TITLE', 'Lasys');
}
if (!defined('ROOT_PATH')) {
    define('ROOT_PATH', dirname(__DIR__));
}
define('DATA_PATH', ROOT_PATH . DS . DATA_DIR);
define('PUB_PATH', ROOT_PATH . DS . PUB_DIR);
define('VIEWS_PATH', ROOT_PATH . DS . VIEWS_DIR);
define('ACTIONS_PATH', ROOT_PATH . DS . ACTIONS_DIR);
set_include_path(
    get_include_path()
        . PATH_SEPARATOR . dirname(__DIR__) . DS . 'vendor'
        . PATH_SEPARATOR . __DIR__
        . PATH_SEPARATOR . __DIR__ . DS . 'utils'
        . PATH_SEPARATOR . __DIR__ . DS . 'parsers'
        . PATH_SEPARATOR .  ACTIONS_PATH
        . PATH_SEPARATOR . __DIR__ . DS . 'actions'
);
// Load it for autoload has not been enabled.
require_once 'str.php';
spl_autoload_register(function ($class) {
    $file = Str::classToFile($class) . '.php';
    require_once $file;
});
