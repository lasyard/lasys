<?php
final class App
{
    private $_home;
    private $_title;
    private $_datum = [];
    private $_scripts = [];
    private $_styles = [];
    private $_breadcrumbs = [];
    private $_base;
    private $_path;
    private $_list;
    private $_item;

    public function __construct()
    {
        date_default_timezone_set("UTC");
        define('CONF_PATH', ROOT_PATH . '/configs');
        require_once CONF_PATH . '/defs.php';
        require_once 'setup.php';
        session_cache_limiter('public');
        session_start();
    }

    public function base()
    {
        return $this->_base;
    }

    public function path()
    {
        return $this->_path;
    }

    public function run()
    {
        list($this->_base, $args) = Server::getHomeAndPath();
        $this->_home = $this->_base;
        $this->_path = DATA_PATH;
        $conf = new Config(CONF_PATH);
        $title = null;
        $item = null;
        try {
            while (!empty($args)) {
                $name = array_shift($args);
                if (empty($name)) {
                    continue;
                }
                if ($conf->isDir($name) || is_dir($this->_path . '/' . $name)) {
                    if ($this->_base != $this->_home) {
                        $this->_breadcrumbs[] = [
                            'text' => $title,
                            'url' => $this->_base,
                        ];
                    }
                    $this->_path .= '/' . $name;
                    $this->_base .= $name . '/';
                    $title = $conf->resolveTitle($name);
                    $conf->shift($name);
                } else {
                    $item = $conf->resolve($this->_path, $name);
                    break;
                }
            }
            if ($item === null) {
                $name = '';
                $item = $conf->resolve($this->_path, 'index');
            }
            if (method_exists($item, 'cook')) {
                $item->cook($args);
            }
        } catch (Exception $e) {
            $item = new ErrorItem($e->getMessage());
        }
        $this->_list = $this->createItemList($conf, $this->createFileList($conf), $name);
        $this->_item = $item;
        $this->_title = APP_TITLE;
        if ($this->_path != DATA_PATH || !empty($name)) {
            $this->_title .= ' - ' . $item->title ?? $conf->resolveTitle($name);
        }
        $this->addScript('js/main');
        $this->addStyle('css/main');
        $this->addStyle('lib/bootstrap-icons');
        $httpHeaders = $item->httpHeaders;
        if (!empty($httpHeaders)) {
            foreach ($httpHeaders as $header) header($header);
        }
        $this->view('main');
    }

    private function createItemList($conf, $files, $selected)
    {
        $base = $this->_base;
        $list0 = [];
        foreach ($conf->list as $name => $item) {
            if ($item['hidden']) {
                if (array_key_exists($name, $files)) {
                    unset($files[$name]);
                }
                continue;
            }
            $isDir = false;
            if ($item['type'] == Config::FILE) {
                if (array_key_exists($name, $files)) {
                    $isDir = $files[$name];
                    unset($files[$name]);
                } else {
                    continue;
                }
            } else if ($item['type'] == Config::DIR) {
                $isDir = true;
            }
            $list0[] = [
                'text' => $item['title'] ?? Str::captalize($name),
                'url' => $base . $name . ($isDir ? '/' : ''),
                'selected' => $name == $selected,
            ];
        }
        $list1 = [];
        foreach ($files as $name => $isDir) {
            $list1[] = [
                'text' => Str::captalize($name),
                'url' => $base . $name . ($isDir ? '/' : ''),
                'selected' => $name == $selected,
            ];
        }
        if ($conf->orderBy) {
            usort($list1, $conf->orderBy);
        }
        return array_merge($list0, $list1);
    }

    private function createFileList($conf)
    {
        $path = $this->_path;
        $dh = @opendir($path);
        if (!$dh) {
            return [];
        }
        $files = [];
        while (($file = readdir($dh)) !== false) {
            if ($conf->excluded($file)) {
                continue;
            }
            if (is_dir("$path/$file")) {
                $name = $file;
                $files[$name] = true;
            } else {
                $name = pathinfo($file, PATHINFO_FILENAME);
                $files[$name] = false;
            }
        }
        closedir($dh);
        return $files;
    }

    public function view($view, $extraVars = [])
    {
        $appVars = [
            'home' => $this->_home,
            'title' => $this->_title,
            'datum' => $this->_datum,
            'scripts' => $this->_scripts,
            'styles' => $this->_styles,
            'base' => $this->_base,
            'breadcrumbs' => $this->_breadcrumbs,
            'list' => $this->_list,
            'content' => $this->_item->content,
        ];
        View::render($view, array_merge($appVars, $extraVars));
    }

    public function pubUrl($args)
    {
        return $this->_home . PUB_DIR . '/' . $args;
    }

    public function addScript($file)
    {
        if (
            !$this->tryAddScript($file . '.js')
            && !$this->tryAddScript('sys/' . $file . '.js')
        ) {
            $this->_scripts[] = $file;
        }
    }

    private function tryAddScript($file)
    {
        if (is_file(PUB_PATH . '/' . $file)) {
            $this->_scripts[] = $this->pubUrl($file);
            return true;
        }
        return false;
    }

    public function addStyle($file)
    {
        if (
            !$this->tryAddStyle($file . '.css')
            && !$this->tryAddStyle('sys/' . $file . '.css')
        ) {
            $this->_styles[] = $file;
        }
    }

    private function tryAddStyle($file)
    {
        if (is_file(PUB_PATH . '/' . $file)) {
            $this->_styles[] = $this->pubUrl($file);
            return true;
        }
        return false;
    }

    public function addData($name, $data, $flags = JSON_UNESCAPED_UNICODE)
    {
        $this->_datum[] = [
            'name' => $name,
            'data' => $data,
            'flags' => $flags,
        ];
    }
}
