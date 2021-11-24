<?php
final class Config
{
    private const DEFAULT = [
        'recursive' => true,
        'listedOnly' => true,
        'excludes' => ['.*', 'index.*', '_*'],
        'orderBy' => false,
        'list' => [],
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
        $file = $this->_path . '/list.php';
        $conf = is_file($file) ? include $file : [];
        $recursive = $this->_conf['recursive'];
        $this->setDefault($conf, 'recursive', $recursive);
        $this->setDefault($conf, 'listedOnly', $recursive);
        $this->mergeArray($conf, 'excludes');
        $this->setDefault($conf, 'orderBy', $recursive);
        $this->setDefault($conf, 'list');
        $this->_conf = $conf;
    }

    public function shift($name)
    {
        $this->_path .= '/' . $name;
        $this->read();
    }

    public function excluded($file)
    {
        if ($this->_conf['listedOnly'] && !isset($this->_conf['list'][pathinfo($file, PATHINFO_FILENAME)])) {
            return true;
        }
        foreach ($this->_conf['excludes'] as $p) {
            if (fnmatch($p, $file)) {
                return true;
            }
        }
        return false;
    }

    public function resolveTitle($name)
    {
        return $this->_conf['list'][$name]['title'] ?? Str::captalize($name);
    }

    public function resolve($path, $name)
    {
        $list = $this->_conf['list'];
        $type = $list[$name]['type'] ?? 'file';
        if ($type === 'file') {
            return FileItem::get($path, $name);
        }
        return new ErrorItem('Unsupported item type "' . $type . '".');
    }
}
