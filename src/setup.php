<?php
define('DS', DIRECTORY_SEPARATOR);
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
