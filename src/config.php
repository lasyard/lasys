<?php
final class Config
{
    // config
    public const TRAITS = 'traits';
    public const READ_ONLY = 'readOnly';
    public const DEFAULT_ITEM = 'defaultItem';
    public const READ_PRIV = 'readPriv';
    public const EDIT_PRIV = 'editPriv';
    public const EXCLUDES = 'excludes';
    public const LIST = 'list';
    public const ETC = '*';

    // item config
    public const TITLE = 'title';
    public const BUTTON = 'button';
    public const HIDDEN = 'hidden';

    private const DEFAULT = [
        self::TRAITS => [],
        self::READ_ONLY => true,
        self::DEFAULT_ITEM => 'index',
        self::READ_PRIV => [],
        self::EDIT_PRIV => [User::OWNER, User::EDIT],
        self::EXCLUDES => ['index.*', '_*'],
        self::LIST => [],
    ];

    private const RECURSIVE_CONF = [
        self::READ_ONLY,
        self::DEFAULT_ITEM,
        self::READ_PRIV,
        self::EDIT_PRIV,
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
        // Set get to view files not in list or with no actions set. Do it here to read `READ_PRIV` conf.
        $conf[self::ETC] = [Server::GET => FileActions::get()->priv($conf[self::READ_PRIV])];
        // Call `forChid` first to allow mangling of new conf.
        foreach ($this->_conf[self::TRAITS] as $trait) {
            $conf = $trait->forChild($conf, $this->_conf);
        }
        foreach ($conf[self::TRAITS] as $trait) {
            $conf = $trait->forSelf($conf, $this->_conf);
        }
        foreach ($conf[self::LIST] as &$item) {
            if (is_string($item)) {
                $item = [self::TITLE => $item];
            } else if ($item instanceof Actions) {
                $item = [Server::GET => $item->priv($conf[self::READ_PRIV])];
            } else if (is_array($item) && isset($item[Actions::ACTION])) {
                $item = [Server::GET => $item];
            } else if (isset($item[Config::TRAITS])) {
                $traits = $item[Config::TRAITS];
                if (is_array($traits)) {
                    foreach ($traits as $trait) {
                        $item = $trait->forItem($item, $conf);
                    }
                } else {
                    $item = $traits->forItem($item, $conf);
                }
            }
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
        return $this->_conf[self::LIST][$name][self::TITLE]
            ?? ($name === $this->_conf[self::DEFAULT_ITEM] ? '' : Str::captalize($name));
    }

    public function action($name, $type)
    {
        $list = $this->_conf[self::LIST];
        if (isset($list[$name][$type])) {
            $action = $list[$name][$type];
            if ($action instanceof Actions) {
                return $action->priv(
                    ...(Server::isEdit($type) ? $this->_conf[self::EDIT_PRIV] : $this->_conf[self::READ_PRIV])
                );
            }
            return $action;
        }
        return null;
    }
}
