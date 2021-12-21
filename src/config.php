<?php
final class Config
{
    public const EDITABLE = 'editable';
    public const SIZE_LIMIT = 'sizeLimit';
    public const ACCEPT = 'accept';
    public const ORDER = 'order';
    public const DEFAULT_ITEM = 'defaultItem';
    public const DEFAULT_PRIV = 'defaultPriv';
    public const EXCLUDES = 'excludes';
    public const LIST = 'list';
    public const TRAITS = 'traits';
    public const TITLE = 'title';
    public const PRIV = 'priv';
    public const HIDDEN = 'hidden';

    private const DEFAULT = [
        self::EDITABLE => false,
        self::SIZE_LIMIT => FileActions::FILE_SIZE_LIMIT,
        self::ACCEPT => 'text/plain',
        self::ORDER => false,
        self::DEFAULT_ITEM => 'index',
        self::DEFAULT_PRIV => [
            Server::GET => [],
            Server::AJAX_GET => [],
        ],
        self::EXCLUDES => ['.*', 'index.*', '_*'],
        self::LIST => [],
    ];

    private const RECURSIVE_CONF = [
        self::EDITABLE,
        self::SIZE_LIMIT,
        self::ACCEPT,
        self::ORDER,
        self::DEFAULT_ITEM,
        self::DEFAULT_PRIV,
    ];

    private $_path;
    private $_conf = Config::DEFAULT;

    public function __construct($path)
    {
        $this->_path = $path;
        $this->read();
    }

    public function __get($var)
    {
        if (array_key_exists($var, $this->_conf)) {
            return $this->_conf[$var];
        }
        throw new Exception('Try to get undefined configuration "' . $var . '".');
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
        $this->setDefault($conf, self::LIST);
        foreach ($conf[self::LIST] as $name => &$item) {
            if (is_string($item)) {
                $item = [self::TITLE => $item];
            } else if ($item instanceof Actions) {
                $item = [self::TITLE => Str::captalize($name), Server::GET => $item];
            }
            if (isset($item[self::PRIV])) {
                if (is_array($item[self::PRIV])) {
                    $item[self::PRIV] = array_map(function ($v) {
                        return explode(' ', $v);
                    }, $item[self::PRIV]);
                } else {
                    $item[self::PRIV] = [Server::GET => explode(' ', $item[self::PRIV])];
                }
            } else {
                $item[self::PRIV] = $conf[self::DEFAULT_PRIV];
            }
        }
        if (isset($conf[self::TRAITS])) {
            $traits = $conf[self::TRAITS];
            if (is_array($traits)) {
                foreach ($traits as $trait) {
                    $trait($conf, $this->_conf);
                }
            } else {
                $traits($conf, $this->_conf);
            }
        }
        $this->_conf = $conf;
    }

    public function shift($name)
    {
        $this->_path .= DS . $name;
        $this->read();
    }

    public function excluded($file)
    {
        if (array_key_exists($file, $this->_conf[self::LIST])) {
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

    public function priv($name, $key = Server::GET)
    {
        $list = $this->_conf[self::LIST];
        $priv = array_key_exists($name, $list) ? $list[$name][self::PRIV] : $this->_conf[self::DEFAULT_PRIV];
        return $priv[$key] ?? [User::ADMIN];
    }

    public function action($name, $key)
    {
        $conf = $this->_conf;
        $list = $conf[self::LIST];
        $action = null;
        if (isset($list[$name][$key])) {
            $action = $list[$name][$key];
        }
        if (!$action) {
            if ($conf[self::EDITABLE]) {
                switch ($key) {
                    case Server::GET:
                        $action = FileActions::get();
                        break;
                    case Server::PUT:
                        if ($conf[self::SIZE_LIMIT] == FileActions::FILE_SIZE_LIMIT) {
                            $action = FileActions::put();
                        } else {
                            $action = FileActions::put($conf[self::SIZE_LIMIT]);
                        }
                        break;
                    case Server::AJAX_DELETE:
                        $action = FileActions::delete();
                        break;
                    default:
                }
            } else if ($key == Server::GET) {
                $action = FileActions::get();
            }
        }
        return $action ?? Actions::default();
    }

    public function dirOrFile($name)
    {
        $file = $this->_path . DS . $name;
        return is_dir($file) ? true : (is_file($file . '.php') ? false : null);
    }

    public static function orderByTime($descend = true)
    {
        if ($descend) {
            return function ($b, $a) {
                return $a['time'] <=> $b['time'];
            };
        }
        return function ($a, $b) {
            return $a['time'] <=> $b['time'];
        };
    }

    public static function orderByName($descend = false)
    {
        if (!$descend) {
            return function ($a, $b) {
                return strnatcasecmp($a['text'], $b['text']);
            };
        }
        return function ($b, $a) {
            return strnatcasecmp($a['text'], $b['text']);
        };
    }

    public static function inheritDefault()
    {
        return function (&$conf, $oldConf) {
            Arr::copyKeys(
                $conf[self::LIST][$conf[self::DEFAULT_ITEM]],
                $oldConf[self::LIST][$conf[self::DEFAULT_ITEM]],
                Server::GET,
                self::HIDDEN,
                self::TITLE,
                self::PRIV,
            );
        };
    }

    public static function doUpload($title, $accept = '*', $sizeLimit = FileActions::FILE_SIZE_LIMIT)
    {
        return function (&$conf, $oldConf) use ($title, $accept, $sizeLimit) {
            $conf[self::EDITABLE] = true;
            $conf[self::ACCEPT] = $accept;
            if ($sizeLimit != FileActions::FILE_SIZE_LIMIT) {
                $conf[self::SIZE_LIMIT] = $sizeLimit;
                $conf[self::LIST][$conf[self::DEFAULT_ITEM]][Server::POST] = FileActions::post($sizeLimit);
            } else {
                $conf[self::LIST][$conf[self::DEFAULT_ITEM]][Server::POST] = FileActions::post();
            }
            $conf[self::LIST][$conf[self::DEFAULT_ITEM]][self::PRIV][Server::POST] = [User::EDIT];
            $conf[self::DEFAULT_PRIV][Server::PUT] = [User::OWNER, User::EDIT];
            $conf[self::DEFAULT_PRIV][Server::AJAX_DELETE] = [User::OWNER, User::EDIT];
            $conf[self::LIST]['upload'] = [
                self::TITLE => '<i class="bi bi-upload sys button"></i>',
                Server::GET => FileActions::uploadForm($title, $accept, $sizeLimit),
                self::PRIV => [Server::GET => [User::EDIT]],
            ];
        };
    }
}
