<?php
final class Config
{
    private const CONFIG_FILE = 'list.php';

    // config
    public const TRAITS = 'traits';
    public const READ_ONLY = 'readOnly';
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
                $item = [Server::GET => $item->priv($conf[self::PRIV_READ])];
            } else if (is_array($item) && isset($item[Actions::ACTION])) {
                $item = [Server::GET => $item];
            }
            self::applyTraits($item, $conf[self::TRAITS], 'forEachItem', $conf);
            if (isset($item[Config::TRAITS])) {
                self::applyTraits($item, $item[self::TRAITS], 'forItem', $conf);
            }
        }
        $this->_conf = $conf;
    }

    public function get($name)
    {
        return array_key_exists($name, $this->_conf) ? $this->_conf[$name] : null;
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
        } else if (is_dir($this->_path . DS . $name)) {
            $action = Actions::noop();
        } else if (isset($this->_conf[Config::ETC][$type])) {
            $action = $this->_conf[Config::ETC][$type];
        } else {
            throw new RuntimeException("No action for $type request on $name");
        }
        if ($action instanceof Actions) {
            return $action->priv();
        }
        return $action;
    }
}
