<?php
final class Config
{
    private const DEFAULT = [
        'editable' => false,
        'accept' => 'text/plain',
        'order' => false,
        'defaultItem' => 'index',
        'defaultPriv' => [
            'GET' => [],
            'POST' => ['edit'],
            'PUT' => ['owner', 'edit'],
            'DELETE' => ['owner', 'edit']
        ],
        'excludes' => ['.*', 'index.*', '_*'],
        'list' => [],
    ];

    private const RECURSIVE_CONF = [
        'editable',
        'accept',
        'order',
        'defaultItem',
        'defaultPriv',
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
        $this->mergeArray($conf, 'excludes');
        $this->setDefault($conf, 'list');
        foreach ($conf['list'] as &$item) {
            if (is_string($item)) {
                $item = ['title' => $item];
            }
            if (is_array($item['priv'])) {
                $item['priv'] = array_map(function ($v) {
                    return explode(' ', $v);
                }, $item['priv']);
            } else if (isset($item['priv'])) {
                $item['priv'] = ['GET' => explode(' ', $item['priv'])];
            } else {
                $item['priv'] = $conf['defaultPriv'];
            }
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

    public function shift($name)
    {
        $this->_path .= DS . $name;
        $this->read();
    }

    public function excluded($file)
    {
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
        return $item['hidden'] ?? false;
    }

    public function title($name)
    {
        return $this->_conf['list'][$name]['title'] ?? Str::captalize($name);
    }

    public function priv($name, $method = 'GET')
    {
        $item = $this->_conf['list'][$name];
        $priv = (isset($item['priv']) ? $item['priv'] : $this->_conf['defaultPriv']);
        return $priv[$method] ?? ['admin'];
    }

    public function action($name)
    {
        $item = $this->_conf['list'][$name];
        $method = Server::requestMethod();
        if (isset($item)) {
            $action = $item[$method];
        }
        if (!$action) {
            if ($this->_conf['editable']) {
                switch ($method) {
                    case 'GET':
                        $action = FileActions::get();
                        break;
                    case 'PUT':
                        $action = FileActions::put();
                        break;
                    case 'DELETE':
                        $action = FileActions::delete();
                        break;
                    case 'POST':
                        $action = FileActions::post();
                        break;
                    default:
                }
            } else if ($method == 'GET') {
                $action = FileActions::get();
            }
        }
        return $action ?? Actions::default();
    }

    public function isDir($name)
    {
        return $this->_conf['list'][$name]['isDir'];
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

    public static function inheritDefault()
    {
        return function (&$conf, $oldConf) {
            Arr::copyKeys(
                $conf['list'][$conf['defaultItem']],
                $oldConf['list'][$conf['defaultItem']],
                'GET',
                'hidden',
                'title'
            );
        };
    }

    public static function doUpload($title, $accept = '*', $sizeLimit = 65536)
    {
        return function (&$conf, $oldConf) use ($title, $accept, $sizeLimit) {
            $conf['editable'] = true;
            $conf['list']['upload'] = [
                'title' => '<i class="bi bi-upload sys button"></i>',
                'GET' => FileActions::uploadForm($title, $accept, $sizeLimit),
                'priv' => ['GET' => ['edit']],
            ];
        };
    }
}
