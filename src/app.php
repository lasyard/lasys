<?php
final class App
{
    public const META_FILE = 'list.json';

    private $_home;
    private $_title;
    private $_datum = [];
    private $_scripts = [];
    private $_styles = [];
    private $_breadcrumbs = [];
    private $_base;
    private $_path;
    private $_name;
    private $_list;
    private $_files;
    private $_action = null;
    private $_vars;

    public function __construct()
    {
        define('CONF_PATH', ROOT_PATH . DIRECTORY_SEPARATOR . 'configs');
        require_once CONF_PATH . DIRECTORY_SEPARATOR . 'defs.php';
        // DS is not define till here.
        require_once 'setup.php';
    }

    public function run()
    {
        list($this->_home, $args) = Server::getHomeAndPath();
        define('PUB_URL', $this->_home . Str::pathUrl(PUB_DIR) . '/');
        session_cache_limiter('public');
        session_start();
        $this->_base = $this->_home;
        $this->_path = DATA_PATH;
        $conf = new Config(CONF_PATH);
        $title = null;
        while (!empty($args)) {
            $name = array_shift($args);
            if ($name == '') {
                continue;
            }
            $this->_name = $name;
            if ($conf->isDir($name) || is_dir($this->_path . DS . $name)) {
                if ($this->_base != $this->_home) {
                    $this->_breadcrumbs[] = [
                        'text' => $title,
                        'url' => $this->_base,
                    ];
                }
                $this->_path .= DS . $name;
                $this->_base .= $name . '/';
                $title = $conf->title($name);
                $conf->shift($name);
            } else {
                $this->_action = $this->resolve($conf);
                break;
            }
        }
        if ($this->_action === null) {
            $this->_name = '';
            $this->_action = $this->resolve($conf);
        }
        if (Server::requestMethod() == 'HEAD') {
            // Seems never run to this.
            $this->header();
            exit;
        }
        // This is needed when cooking.
        $this->createFileList($conf);
        $this->_action->cook($args, $this->_base, $this->_path, $this->_name);
        $this->_list = $this->createItemList($conf, $name);
        $this->_title = APP_TITLE;
        if ($this->_path != DATA_PATH || !empty($name)) {
            $this->_title .= ' - ' . $this->_action->title ?? $conf->title($name);
        }
        $this->addScript('js' . DS . 'main');
        $this->addStyle('css' . DS . 'main');
        $this->addStyle('lib' . DS . 'bootstrap-icons');
        $this->_vars = [
            'home' => $this->_home,
            'title' => $this->_title,
            'datum' => $this->_datum,
            'scripts' => $this->_scripts,
            'styles' => $this->_styles,
            'base' => $this->_base,
            'breadcrumbs' => $this->_breadcrumbs,
            'meta' => $this->metaView(),
            'list' => $this->_list,
            'content' => $this->_action->content,
        ];
        $this->header();
        $this->view('main');
    }

    private function resolve($conf)
    {
        $action = $conf->action($this->_name);
        if ($action) {
            return $action;
        }
        switch (Server::requestMethod()) {
            case 'GET':
                return FileActions::get();
            case 'DELETE':
                return FileActions::delete();
        }
        return Actions::default();
    }

    private function header()
    {
        $httpHeaders = $this->_action->httpHeaders;
        if (isset($httpHeaders)) {
            foreach ($httpHeaders as $header) {
                header($header);
            }
        }
    }

    private function makeItem($name, $info, $selected)
    {
        return [
            'text' => $info['title'] ?? Str::captalize(pathinfo($name, PATHINFO_FILENAME)),
            'url' => $this->_base . $name . ($info['isDir'] ? '/' : ''),
            'selected' => $name === $selected,
        ];
    }

    private function createItemList($conf, $selected)
    {
        $base = $this->_base;
        $files = $this->_files;
        $list0 = [];
        foreach ($conf->list as $name => $item) {
            if ($item['hidden'] || !Sys::user()->hasPriv($item['priv'])) {
                if (array_key_exists($name, $files)) {
                    unset($files[$name]);
                }
                continue;
            }
            if ($item['type'] == Config::FILE) {
                if (array_key_exists($name, $files)) {
                    $file = $files[$name];
                    $item['isDir'] = $file['isDir'];
                    $item['title'] = $file['title'] ?? $item['title'];
                    unset($files[$name]);
                } else {
                    continue;
                }
            } else if ($item['type'] == Config::DIR) {
                $item['isDir'] = true;
            }
            $list0[] = $this->makeItem($name, $item, $selected);
        }
        $list1 = [];
        foreach ($files as $name => $file) {
            $list1[] = $this->makeItem($name, $file, $selected);
        }
        if ($conf->order) {
            usort($list1, $conf->order);
        }
        return array_merge($list0, $list1);
    }

    private function createFileList($conf)
    {
        $path = $this->_path;
        $dh = @opendir($path);
        if (!$dh) {
            $this->_files = [];
            return;
        }
        $files = [];
        while (($file = readdir($dh)) !== false) {
            if ($file == self::META_FILE) {
                $files = array_merge_recursive(
                    $files,
                    json_decode(file_get_contents($path . DS . $file), true)
                );
                continue;
            }
            if ($conf->excluded($file)) {
                continue;
            }
            if (is_dir($path . DS . $file)) {
                $files[$file]['isDir'] = true;
            } else {
                $files[$file]['isDir'] = false;
            }
        }
        closedir($dh);
        $this->_files = array_filter($files, function ($v) {
            return array_key_exists('isDir', $v);
        });
        if (count($this->_files) < count($files)) {
            $this->saveMeta();
        }
    }

    public function addFile($name, $fileInfo)
    {
        $this->_files[$name] = $fileInfo;
        $this->_files[$name]['isDir'] = false;
        $this->saveMeta();
    }

    private function saveMeta()
    {
        $json = [];
        foreach ($this->_files as $name => $file) {
            $res = Arr::transKeys($file, 'title', 'time', 'user');
            if (!empty($res)) {
                $json[$name] = $res;
            }
        }
        if (!empty($json)) {
            $path = $this->_path;
            if (!is_dir($path)) {
                mkdir($path, 0775, true);
            }
            file_put_contents(
                $path . DS . App::META_FILE,
                json_encode($json, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)
            );
        }
    }

    private function metaView()
    {
        $name = $this->_name;
        $meta = $this->_files[$name];
        if (isset($meta) && !$meta['isDir']) {
            if (Sys::user()->hasPriv('edit')) {
                $meta['edit'] = true;
                $meta['name'] = $name;
            }
            return View::renderHtml('meta', $meta);
        }
        return '';
    }

    public function view($view, $extraVars = [])
    {
        View::render($view, array_merge($this->_vars, $extraVars));
    }

    public function addScript($file)
    {
        if (
            !$this->tryAddScript($file . '.js')
            && !$this->tryAddScript('sys' . DS . $file . '.js')
        ) {
            $this->_scripts[] = $file;
        }
    }

    private function tryAddScript($file)
    {
        if (is_file(PUB_PATH . DS . $file)) {
            $this->_scripts[] = PUB_URL . Str::pathUrl($file);
            return true;
        }
        return false;
    }

    public function addStyle($file)
    {
        if (
            !$this->tryAddStyle($file . '.css')
            && !$this->tryAddStyle('sys' . DS . $file . '.css')
        ) {
            $this->_styles[] = $file;
        }
    }

    private function tryAddStyle($file)
    {
        if (is_file(PUB_PATH . DS . $file)) {
            $this->_styles[] = PUB_URL . Str::pathUrl($file);
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

    public function redirect($name)
    {
        $url = (strpos($name, '//') === false) ? $this->_base . $name : $name;
        header('Location: ' . $url);
        exit;
    }
}
