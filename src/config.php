<?php
final class Config
{
    public const FILE = 'file';
    public const PHP = 'php';
    public const DIR = 'dir';

    private const DEFAULT = [
        'recursive' => true,
        'exclusive' => true,
        'order' => false,
        'defaultItem' => 'index',
        'defaultType' => self::FILE,
        'excludes' => ['.*', 'index.*', '_*'],
        'list' => [],
    ];

    private const RECURSIVE_CONF = [
        'recursive',
        'exclusive',
        'order',
        'defaultItem',
        'defaultType',
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

    private function defaultActions($item)
    {
        $actions = [
            'GET' => null,
            'PUT' => null,
            'POST' => null,
            'DELETE' => null,
        ];
        if (!isset($item['actions'])) {
            switch ($item['type']) {
                case self::FILE:
                    $actions['GET'] = FileActions::get();
                    $actions['PUT'] = FileActions::put();
                    $actions['DELETE'] = FileActions::delete();
                    break;
                case self::PHP:
                    $actions['GET'] = Actions::default();
                    break;
            }
        } else if ($item['actions'] instanceof Actions) {
            $actions['GET'] = $item['actions'];
        } else if (is_array($item['actions'])) {
            Arr::copyKeys($actions, $item['actions'], 'GET', 'PUT', 'POST', 'DELETE');
        }
        return $actions;
    }

    private function read()
    {
        $file = $this->_path . '/list.php';
        $conf = is_file($file) ? include $file : [];
        $recursive = $this->_conf['recursive'];
        foreach (self::RECURSIVE_CONF as $c) {
            $this->setDefault($conf, $c, $recursive);
        }
        $this->mergeArray($conf, 'excludes');
        $this->setDefault($conf, 'list');
        foreach ($conf['list'] as &$item) {
            if (is_string($item)) {
                $item = ['title' => $item];
            }
            $item['type'] = $item['type'] ?? $conf['defaultType'];
            $item['hidden'] = $item['hidden'] ?? false;
            $item['actions'] = $this->defaultActions($item);
            $item['priv'] = $item['priv'] ?? '';
        }
        $this->_conf = $conf;
    }

    private function checkPriv($name)
    {
        if (!Sys::user()->hasPriv($this->_conf['list'][$name]['priv'])) {
            throw new RuntimeException('You do not have privilege to access this item.');
        }
    }

    public function shift($name)
    {
        $this->checkPriv($name);
        $this->_path .= '/' . $name;
        $this->read();
    }

    public function excluded($file)
    {
        if ($this->_conf['exclusive'] && !isset($this->_conf['list'][pathinfo($file, PATHINFO_FILENAME)])) {
            return true;
        }
        foreach ($this->_conf['excludes'] as $p) {
            if (fnmatch($p, $file)) {
                return true;
            }
        }
        return false;
    }

    public function title($name)
    {
        return $this->_conf['list'][$name]['title'] ?? Str::captalize($name);
    }

    public function action($name)
    {
        if ($name === '') {
            $name = $this->_conf['defaultItem'];
        }
        $this->checkPriv($name);
        $item = $this->_conf['list'][$name];
        if (isset($item)) {
            return $item['actions'][Server::requestMethod()];
        }
        return null;
    }

    public function isDir($name)
    {
        return $this->_conf['list'][$name]['type'] == self::DIR;
    }
}
