<?php
final class Config
{
    private const DEFAULT = [
        'isDir' => false,
        'exclusive' => true,
        'order' => false,
        'defaultItem' => 'index',
        'excludes' => ['.*', 'index.*', '_*'],
        'list' => [],
    ];

    private const RECURSIVE_CONF = [
        'exclusive',
        'order',
        'defaultItem',
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
        $actions = [];
        if (!isset($item['actions'])) {
            $actions['GET'] = FileActions::get();
        } else if ($item['actions'] instanceof Actions) {
            $actions['GET'] = $item['actions'];
        } else if (is_array($item['actions'])) {
            Arr::copyKeys($actions, $item['actions'], 'GET', 'PUT', 'POST', 'DELETE');
        }
        return $actions;
    }

    private function read()
    {
        $file = $this->_path . DS . 'list.php';
        $conf = is_file($file) ? include $file : [];
        foreach (self::RECURSIVE_CONF as $c) {
            $this->setDefault($conf, $c, true);
        }
        $this->mergeArray($conf, 'excludes');
        $this->setDefault($conf, 'list');
        foreach ($conf['list'] as &$item) {
            if (is_string($item)) {
                $item = ['title' => $item];
            }
            $item['actions'] = $this->defaultActions($item);
            $item['priv'] = $item['priv'] ?? '';
        }
        if (isset($conf['traits'])) {
            $traits = $conf['traits'];
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

    private function checkPriv($name)
    {
        if (!Sys::user()->hasPriv($this->_conf['list'][$name]['priv'])) {
            throw new RuntimeException('You do not have privilege to access this item.');
        }
    }

    public function shift($name)
    {
        $this->checkPriv($name);
        $this->_path .= DS . $name;
        $this->read();
    }

    public function excluded($file)
    {
        if ($this->_conf['exclusive'] && !isset($this->_conf['list'][$file])) {
            return true;
        }
        foreach ($this->_conf['excludes'] as $p) {
            if (fnmatch($p, $file)) {
                return true;
            }
        }
        return false;
    }

    public function hidden($name)
    {
        $item = $this->_conf['list'][$name];
        return $item['hidden'] || !isset($item['actions']['GET']);
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
        return $this->_conf['list'][$name]['isDir'];
    }

    public static function inheritDefault()
    {
        return function (&$conf, $oldConf) {
            $conf['list'][$conf['defaultItem']]['actions']['GET']
                = $oldConf['list'][$oldConf['defaultItem']]['actions']['GET'];
            $conf['list'][$conf['defaultItem']]['hidden']
                = $oldConf['list'][$oldConf['defaultItem']]['hidden'];
        };
    }

    public static function doUpload($title, $accept = '*', $sizeLimit = 65536)
    {
        return function (&$conf, $oldConf) use ($title, $accept, $sizeLimit) {
            $conf['list'][$conf['defaultItem']]['actions']['POST'] = FileActions::post($sizeLimit);
            $conf['list']['upload'] = [
                'title' => '<i class="bi bi-upload sys button"></i>',
                'priv' => 'edit',
            ];
            $conf['list']['upload']['actions']['GET'] = FileActions::uploadForm($title, $accept, $sizeLimit);
        };
    }
}
