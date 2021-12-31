<?php
final class Config
{
    // config
    public const TRAITS = 'traits';
    public const READ_ONLY = 'readOnly';
    public const DEFAULT_ITEM = 'defaultItem';
    public const EXCLUDES = 'excludes';
    public const LIST = 'list';

    // item config
    public const TITLE = 'title';
    public const BUTTON = 'button';
    public const HIDDEN = 'hidden';

    private const DEFAULT = [
        self::TRAITS => [],
        self::READ_ONLY => true,
        self::DEFAULT_ITEM => 'index',
        self::EXCLUDES => ['.*', 'index.*', '_*'],
        self::LIST => [],
    ];

    private const RECURSIVE_CONF = [
        self::READ_ONLY,
        self::DEFAULT_ITEM,
    ];

    private $_path;
    private $_conf = Config::DEFAULT;

    public function __construct($path)
    {
        $this->_path = $path;
        $this->read();
    }

    public function get($name)
    {
        return array_key_exists($name, $this->_conf) ? $this->_conf[$name] : null;
    }

    private function setDefault(&$conf, $opt, $recursive = false)
    {
        if (!isset($conf[$opt])) {
            $conf[$opt] = $recursive ? $this->_conf[$opt] : Config::DEFAULT[$opt];
        }
    }

    private function mergeArray(&$conf, $opt)
    {
        $conf[$opt] = array_unique($conf[$opt] ?? [] + $this->_conf[$opt]);
    }

    private function read()
    {
        $file = $this->_path . DS . 'list.php';
        $conf = is_file($file) ? include $file : [];
        foreach (self::RECURSIVE_CONF as $c) {
            $this->setDefault($conf, $c, true);
        }
        $this->mergeArray($conf, self::EXCLUDES);
        $this->setDefault($conf, self::TRAITS);
        $this->setDefault($conf, self::LIST);
        foreach ($conf[self::LIST] as $name => &$item) {
            if (is_string($item)) {
                $item = [self::TITLE => $item];
            } else if ($item instanceof Actions || is_array($item) && isset($item[Actions::ACTION])) {
                $item = [Server::GET => $item];
            } else if (isset($item[Config::TRAITS])) {
                $traits = $item[Config::TRAITS];
                if (is_array($traits)) {
                    foreach ($traits as $trait) {
                        $item = $trait($item);
                    }
                } else {
                    $item = $traits($item);
                }
            }
        }
        foreach ($this->_conf[self::TRAITS] as $trait) {
            $conf = $trait->forChild($conf, $this->_conf);
        }
        foreach ($conf[self::TRAITS] as $trait) {
            $conf = $trait->forSelf($conf, $this->_conf);
        }
        $this->_conf = $conf;
    }

    public function shift($name)
    {
        $this->_path .= DS . $name;
        $this->read();
    }

    public function list()
    {
        return $this->_conf[self::LIST];
    }

    public function excluded($file)
    {
        if (array_key_exists($file, $this->list())) {
            return true;
        }
        foreach ($this->_conf[self::EXCLUDES] as $p) {
            if (fnmatch($p, $file)) {
                return true;
            }
        }
        return false;
    }

    public function hidden($name)
    {
        $item = $this->_conf[self::LIST][$name];
        return $item[self::HIDDEN] ?? false;
    }

    public function title($name)
    {
        return $this->_conf[self::LIST][$name][self::TITLE] ?? Str::captalize($name);
    }

    public function action($name, $type)
    {
        $list = $this->_conf[self::LIST];
        if (isset($list[$name][$type])) {
            $action = $list[$name][$type];
            if ($action instanceof Actions) {
                return [Actions::ACTION => $action, Actions::PRIV => []];
            }
            return $action;
        }
        return null;
    }

    public function dirOrFile($name)
    {
        $file = $this->_path . DS . $name;
        return is_dir($file) ? true : (is_file($file . '.php') ? false : null);
    }
}
