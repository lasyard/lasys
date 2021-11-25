<?php
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
define('DATA_PATH', ROOT_PATH . '/' . DATA_DIR);
define('PUB_PATH', ROOT_PATH . '/' . PUB_DIR);
define('VIEWS_PATH', ROOT_PATH . '/' . VIEWS_DIR);
define('ACTIONS_PATH', ROOT_PATH . '/' . ACTIONS_DIR);
set_include_path(
    get_include_path()
        . PATH_SEPARATOR . __DIR__
        . PATH_SEPARATOR . __DIR__ . '/utils'
        . PATH_SEPARATOR . __DIR__ . '/items'
        . PATH_SEPARATOR . ACTIONS_PATH
);
spl_autoload_register(function ($class) {
    $words = preg_split('/(?=[A-Z])/', $class, -1, PREG_SPLIT_NO_EMPTY);
    foreach ($words as &$word) {
        $word = strtolower($word);
    }
    $file = implode('_', $words) . '.php';
    require_once $file;
});
