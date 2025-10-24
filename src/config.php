<?php
final class Config
{
    private const CONFIG_FILE = 'list.php';

    // config
    public const TRAITS = 'traits';
    public const READ_ONLY = 'readOnly';
    public const RAW_PAGE = 'rawPage';
    public const DEFAULT_ITEM = 'defaultItem';
    public const EXCLUDES = 'excludes';
    public const LIST = 'list';
    public const ETC = '*';
    public const PRIV_READ = 'privRead';
    public const PRIV_EDIT = 'privEdit';
    public const PRIV_POST = 'privPost';
    public const IS_DIR = 'isDir';
    public const SCRIPTS = 'scripts';
    public const STYLES = 'styles';

    // item config
    public const TITLE = 'title';
    public const DESC = 'desc';
    public const BUTTON = 'button';
    public const HIDDEN = 'hidden';

    // common config
    public const ORDER = 'order';

    private const DEFAULT = [
        self::TRAITS => [],
        self::READ_ONLY => true,
        self::DEFAULT_ITEM => 'index',
        self::EXCLUDES => ['_*'],
        self::LIST => [],
        self::PRIV_READ => [],
        self::PRIV_EDIT => [User::OWNER, User::EDIT],
        self::PRIV_POST => [User::EDIT],
    ];

    private const RECURSIVE_CONF = [
        self::READ_ONLY,
        self::DEFAULT_ITEM,
        self::PRIV_READ,
        self::PRIV_EDIT,
        self::PRIV_POST,
    ];

    private $_path;
    private $_conf;

    public static function root($path)
    {
        return new Config($path, self::DEFAULT);
    }

    private function __construct($path, $oldConf, $traits = [])
    {
        $this->_path = $path;
        $file = $this->_path . DS . self::CONFIG_FILE;
        $conf = is_file($file) ? include $file : [];
        foreach (self::RECURSIVE_CONF as $c) {
            self::setDefault($conf, $c, $oldConf);
        }
        self::setDefault($conf, self::TRAITS);
        self::setDefault($conf, self::LIST);
        $conf[self::EXCLUDES] = Arr::uniqueMerge($conf[self::EXCLUDES], $oldConf[self::EXCLUDES]);
        // Call `forChid` first to allow mangling of new conf.
        self::applyTraits($conf, $traits, 'forChild', $oldConf);
        self::applyTraits($conf, $conf[self::TRAITS], 'forSelf', $oldConf);
        foreach ($conf[self::LIST] as &$item) {
            if (is_string($item)) {
                $item = [self::TITLE => $item];
            } else if ($item instanceof Actions) {
                $item = [Server::GET => $item];
            } else if (is_array($item) && isset($item[Actions::ACTION])) {
                $item = [Server::GET => $item];
            }
            self::applyTraits($item, $conf[self::TRAITS], 'forEachItem', $conf);
            if (isset($item[self::TRAITS])) {
                self::applyTraits($item, $item[self::TRAITS], 'forItem', $conf);
            }
        }
        $this->_conf = $conf;
    }

    public function get($name)
    {
        return $this->_conf[$name] ?? null;
    }

    private static function setDefault(&$conf, $opt, $oldConf = null)
    {
        if (!isset($conf[$opt])) {
            $conf[$opt] = $oldConf ? $oldConf[$opt] : self::DEFAULT[$opt];
        }
    }

    private static function applyTraits(&$target, $traits, $method, $conf)
    {
        Arr::forOneOrMany($traits, function ($trait) use ($method, &$target, $conf) {
            $trait->$method($target, $conf);
        });
    }

    public function read($name)
    {
        return new Config(
            $this->_path . DS . $name,
            $this->_conf,
            $this->_conf[self::LIST][$name][self::TRAITS] ?? []
        );
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
        return $this->_conf[self::LIST][$name][self::HIDDEN] ?? false;
    }

    public function raw($name)
    {
        return $this->_conf[self::LIST][$name][self::RAW_PAGE] ?? false;
    }

    public function title($name)
    {
        return $this->_conf[self::LIST][$name][self::TITLE]
            ?? ($name === $this->_conf[self::DEFAULT_ITEM] ? '' : Str::captalize($name));
    }

    public function attr($name, $key)
    {
        $conf = $this->_conf;
        $list = $conf[self::LIST];
        return $list[$name][$key] ?? $conf[self::ETC][$key] ?? $conf[$key];
    }

    public function action($name, $type)
    {
        $conf = $this->_conf;
        $list = $conf[self::LIST];
        if (isset($list[$name][$type])) {
            $action = $list[$name][$type];
        } else if (is_dir($this->_path . DS . $name)) {
            $action = Actions::noop(...$this->attr($name, self::PRIV_READ));
        } else if (isset($conf[self::ETC][$type])) {
            $action = $conf[self::ETC][$type];
        } else {
            return Actions::nil();
        }
        if ($action instanceof Actions) {
            switch ($type) {
                case Server::GET:
                case Server::AJAX_GET:
                    return $action->priv(...$this->attr($name, self::PRIV_READ));
                case Server::POST:
                    return $action->priv(...$this->attr($name, self::PRIV_POST));
                case Server::AJAX_POST:
                case Server::UPDATE:
                case Server::AJAX_UPDATE:
                case Server::DELETE:
                case Server::AJAX_DELETE:
                    return $action->priv(...$this->attr($name, self::PRIV_EDIT));
                default:
                    break;
            }
            return $action->priv();
        }
        return $action;
    }
}
