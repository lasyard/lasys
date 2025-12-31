<?php
final class Config
{
    public const CONFIG_FILE = 'list.php';
    public const META_FILE = 'list.json';

    // config
    public const DEFAULT_ITEM = 'defaultItem';
    public const EXCLUDES = 'excludes';
    public const LIST = 'list';
    public const TRAITS = 'traits';
    public const RAW_PAGE = 'rawPage';
    public const ETC = '*';
    public const SCRIPTS = 'scripts';
    public const STYLES = 'styles';

    // item config
    public const CONF = 'conf';
    public const ICON = 'icon';
    public const HIDDEN = 'hidden';
    public const META = 'info';
    public const TYPE = 'type';
    public const TITLE = 'title';
    public const DESC = 'desc';

    // item types
    public const CTL = 'ctl';
    public const BTN = 'btn';
    public const DIR = 'dir';

    // common config
    public const PRIV_READ = 'privRead';
    public const PRIV_EDIT = 'privEdit';
    public const PRIV_POST = 'privPost';
    public const ORDER = 'order';

    private $_path;
    private $_data;
    private $_conf;
    private $_fileScanned = false;
    private $_metaLoaded = false;

    private static function _applyTraitsTo(&$target, $entity, $method, $conf)
    {
        if (isset($entity[self::TRAITS])) {
            foreach (Arr::toArray($entity[self::TRAITS]) as $trait) {
                $trait->$method($target, $conf);
            }
        }
    }

    private static function _metaOf($item, $key)
    {
        return $item[$key] ?? $item[self::META][$key] ?? null;
    }

    public static function root($path, $data)
    {
        return new Config($path, $data, [
            self::TRAITS => [],
            self::DEFAULT_ITEM => 'index',
            self::EXCLUDES => [self::META_FILE, '.*', '_*'],
            self::LIST => [],
            self::PRIV_READ => User::NONE,
            self::PRIV_EDIT => User::OWNER_EDIT,
            self::PRIV_POST => User::EDIT,
        ]);
    }

    private function __construct($path, $data, $oldConf, $name = null)
    {
        $this->_path = $path;
        $this->_data = $data;
        // from me in parent conf
        $me = $oldConf[self::LIST][$name] ?? null;
        $conf = $me[self::CONF] ?? [];
        Arr::copyNonExistingKeys(
            $conf,
            $me,
            self::PRIV_READ,
            self::PRIV_EDIT,
            self::PRIV_POST,
            self::ORDER
        );
        $conf[self::TRAITS] ??= [];
        // from config file
        $file = $this->_path . DS . self::CONFIG_FILE;
        if (is_file($file)) {
            $conf = array_merge_recursive(include $file);
        }
        $conf[self::LIST] ??= [];
        foreach ($conf[self::LIST] as &$item) {
            $item[self::TYPE] ??= self::CTL;
        }
        // propagate default
        Arr::copyNonExistingKeys(
            $conf,
            $oldConf,
            self::DEFAULT_ITEM,
            self::PRIV_READ,
            self::PRIV_EDIT,
            self::PRIV_POST,
            self::ORDER
        );
        $conf[self::EXCLUDES] = Arr::uniqueMerge($conf[self::EXCLUDES] ?? [], $oldConf[self::EXCLUDES] ?? []);
        // call `forChid` first to allow mangling of new conf.
        self::_applyTraitsTo($conf, $oldConf, 'forChild', $oldConf);
        self::_applyTraitsTo($conf, $me, 'forMe', $oldConf);
        self::_applyTraitsTo($conf, $conf, 'forSelf', $oldConf);
        $this->_conf = $conf;
    }

    private function _loadMeta()
    {
        $meta = $this->_data . DS . self::META_FILE;
        $conf = &$this->_conf;
        if (is_file($meta)) {
            $files = json_decode(file_get_contents($meta), true);
            foreach ($files as $name => $info) {
                $conf[self::LIST][$name][self::META] = $info;
            }
        }
        $this->_metaLoaded = true;
    }

    private function _scanFile()
    {
        if (!isset($this->_conf[self::ETC][Server::GET])) {
            return;
        }
        $data = $this->_data;
        $dh = @opendir($data);
        $list = &$this->_conf[self::LIST];
        foreach ($list as &$item) {
            if (isset($item[self::TYPE])) {
                continue;
            }
            $info = &$item[self::META];
            if (!isset($info[self::TYPE]) || $info[self::TYPE] == self::DIR) {
                $info['c'] = true;
            }
        }
        $needRefresh = false;
        if ($dh) {
            while (($file = readdir($dh)) !== false) {
                $isDir = is_dir($data . DS . $file);
                if (!$this->excluded($file)) {
                    if (isset($list[$file][self::META])) {
                        $info = &$list[$file][self::META];
                        if (isset($info[self::TYPE])) {
                            if ($info[self::TYPE] === self::DIR && !$isDir) {
                                unset($info[self::TYPE]);
                                $needRefresh = true;
                            }
                        } else if ($isDir) {
                            $info[self::TYPE] = self::DIR;
                            $needRefresh = true;
                        }
                        unset($info['c']);
                    } else {
                        $list[$file] = ($isDir
                            ? [self::META => [self::TYPE => self::DIR]]
                            : [self::META => []]);
                        $needRefresh = true;
                    }
                }
            }
            closedir($dh);
        }
        // do not use `$item`, `$info` as value name, they are refs
        foreach ($list as $name => $v) {
            if (isset($v[self::META]['c'])) {
                unset($list[$name]);
                $needRefresh = true;
            }
        }
        // this must be ahead of `saveMeta`
        $this->_fileScanned = true;
        // correct info
        if ($needRefresh) {
            $this->saveMeta();
        }
    }

    public function read($name)
    {
        return new Config($this->_path . DS . $name, $this->_data . DS . $name, $this->_conf, $name);
    }

    private function _findItem($name)
    {
        while (true) {
            if (isset($this->_conf[self::LIST][$name])) {
                return $this->_conf[self::LIST][$name];
            } else if (!$this->_metaLoaded) {
                $this->_loadMeta();
            } else if (!$this->_fileScanned) {
                $this->_scanFile();
            } else {
                return null;
            }
        }
    }

    public function action($name, $type)
    {
        $item = $this->_findItem($name);
        if (!isset($item)) {
            return Actions::nil();
        }
        if (is_string($item)) {
            $item = [self::TITLE => $item];
        } else if ($item instanceof Actions) {
            $item = [Server::GET => $item];
        }
        $conf = $this->_conf;
        self::_applyTraitsTo($item, $conf, 'forEachItem', $conf);
        if (isset($item[self::TRAITS])) {
            self::_applyTraitsTo($item, $item, 'forItem', $conf);
        }
        // save back to conf
        $this->_conf[self::LIST][$name] = $item;
        if (isset($item[$type])) {
            $action = $item[$type];
        } else if ($this->meta($name, self::TYPE) === self::DIR) {
            $action = Actions::dir($this->attr($name, self::PRIV_READ));
        } else if (isset($conf[self::ETC][$type])) {
            $action = $conf[self::ETC][$type];
        } else {
            return Actions::nil();
        }
        if ($action instanceof Actions) {
            switch ($type) {
                case Server::GET:
                case Server::AJAX_GET:
                    return $action->priv($this->attr($name, self::PRIV_READ));
                case Server::POST:
                    return $action->priv($this->attr($name, self::PRIV_POST));
                case Server::AJAX_POST:
                case Server::UPDATE:
                case Server::AJAX_UPDATE:
                case Server::DELETE:
                case Server::AJAX_DELETE:
                    return $action->priv($this->attr($name, self::PRIV_EDIT));
                default:
                    break;
            }
            return $action->priv();
        }
        return $action;
    }

    public function attr($name, $key)
    {
        $conf = $this->_conf;
        $list = $conf[self::LIST];
        return $list[$name][$key] ?? $conf[self::ETC][$key] ?? $conf[$key] ?? null;
    }

    public function mergeAttr($name, $key)
    {
        $conf = $this->_conf;
        return Arr::uniqueMerge($conf[self::LIST][$name][$key] ?? [], $conf[$key] ?? []);
    }

    public function meta($name, $key)
    {
        $list = $this->partialList();
        return self::_metaOf($list[$name], $key);
    }

    public function get($name)
    {
        return $this->_conf[$name] ?? null;
    }

    public function partialList()
    {
        if (!$this->_metaLoaded) {
            $this->_loadMeta();
        }
        return $this->_conf[self::LIST];
    }

    public function list()
    {
        if (!$this->_metaLoaded) {
            $this->_loadMeta();
        }
        if (!$this->_fileScanned) {
            $this->_scanFile();
        }
        return $this->_conf[self::LIST];
    }

    // include dirs
    public function files()
    {
        return array_filter($this->list(), function ($item) {
            $meta = self::_metaOf($item, self::TYPE);
            return $meta === null || $meta === self::DIR;
        });
    }

    public function excluded($file)
    {
        foreach ($this->_conf[self::EXCLUDES] as $p) {
            if (fnmatch($p, $file)) {
                return true;
            }
        }
        return false;
    }

    public function info($name)
    {
        $list = $this->partialList();
        return $list[$name][self::META] ?? [];
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
        $title = $this->meta($name, self::TITLE);
        if (isset($title)) {
            return $title;
        }
        if ($name === $this->_conf[self::DEFAULT_ITEM]) {
            return '';
        }
        $type = $this->meta($name, self::TYPE);
        $t = (!isset($type) ? pathinfo($name, PATHINFO_FILENAME) : $name);
        return  Str::captalize($t);
    }

    public function saveMeta()
    {
        $files = $this->files();
        $json = [];
        foreach ($files as $name => $item) {
            if (isset($item[self::META])) {
                $json[$name] = $item[self::META];
            }
        }
        $data = $this->_data;
        File::mkdir($data);
        file_put_contents(
            $data . DS . self::META_FILE,
            json_encode($json, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . PHP_EOL
        );
    }

    public function setInfo($name, $info)
    {
        $this->_conf[self::LIST][$name][self::META] = $info;
        $this->saveMeta();
    }

    private static function cmpType($a, $b)
    {
        if ($a[Config::TYPE] ?? false) {
            if ($b[Config::TYPE] ?? false) {
                return Common::cmpIndex(Config::TITLE, strnatcasecmp(...))($a, $b);
            } else {
                return -1;
            }
        }
        if ($b[Config::TYPE] ?? false) {
            return 1;
        }
        return 0;
    }

    public static function orderBy($index, $descend = true)
    {
        $func = ($index == self::TITLE ? strnatcasecmp(...) : Common::cmp(...));
        $func = Common::cmpIndex($index, $func);
        if ($descend) {
            $func = Common::invertCmp($func);
        }
        $func = Common::chainCmps(self::cmpType(...), $func);
        return $func;
    }
}
