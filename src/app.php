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
    private $_conf;
    private $_name;
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
        list($this->_home, $args, $key) = Server::getHomeAndPath();
        define('PUB_URL', $this->_home . Str::pathUrl(PUB_DIR) . '/');
        session_cache_limiter('nocache');
        session_start();
        $this->_base = $this->_home;
        $this->_path = DATA_PATH;
        $this->_conf = new Config(CONF_PATH);
        $title = null;
        while (!empty($args)) {
            $name = array_shift($args);
            if ($name == '') {
                continue;
            }
            $this->_name = $name;
            if ($this->isDir($name)) {
                if ($this->_base != $this->_home) {
                    $this->_breadcrumbs[] = [
                        'text' => $title,
                        'url' => $this->_base,
                    ];
                }
                $this->_path .= DS . $name;
                $this->_base .= $name . '/';
                $title = $this->_conf->title($name);
                if ($this->hasPriv($name)) {
                    $this->_conf->shift($name);
                } else {
                    $this->_action = Actions::error('You do not have privilege to view "' . $name . '".');
                    break;
                }
            } else {
                $this->_action = $this->_conf->action($name, $key);
                break;
            }
        }
        if ($this->_action === null) {
            $this->_name = $this->_conf->defaultItem;
            $this->_action = $this->_conf->action($this->_name, $key);
        }
        if ($key == Server::HEAD) {
            // Seems never run to this.
            $this->header();
            exit;
        }
        // This is needed when calling action->do, e.g., for file uploading.
        $this->createFileList();
        if ($this->hasPriv($this->_name, $key)) {
            $this->_action->do($args, $this->_base, $this->_path, $this->_name);
        } else {
            $this->_action->doError('You do not have privilege to do this.');
        }
        if (Server::isAjax($key)) {
            echo $this->_action->content;
            exit;
        }
        $this->_title = APP_TITLE;
        if ($this->_path != DATA_PATH || !empty($this->_name)) {
            $this->_title .= ' - ' . $this->_action->title ?? $this->_conf->title($this->_name);
        }
        $this->addScript('js' . DS . 'main');
        $this->addStyle('css' . DS . 'main');
        $this->addStyle('lib' . DS . 'bootstrap-icons');
        $list = $this->createItemList();
        $this->_vars = [
            'home' => $this->_home,
            'title' => $this->_title,
            'datum' => $this->_datum,
            'scripts' => $this->_scripts,
            'styles' => $this->_styles,
            'base' => $this->_base,
            'breadcrumbs' => $this->_breadcrumbs,
            'meta' => $this->metaView(),
            'buttons' => $list['buttons'],
            'files' => $list['files'],
            'content' => $this->_action->content,
        ];
        $this->header();
        $this->view('main');
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

    private function makeButton($name, $info)
    {
        return [
            'text' => $info[Config::BUTTON],
            'title' => $info[Config::TITLE] ?? Str::captalize($name),
            'url' => $this->_base . $name,
        ];
    }

    private function makeItem($name, $info, $selected)
    {
        return [
            'text' => $info[Config::TITLE] ?? Str::captalize(pathinfo($name, PATHINFO_FILENAME)),
            'url' => $this->_base . $name . ($info['isDir'] ? '/' : ''),
            'selected' => $name === $selected,
        ];
    }

    private function createItemList()
    {
        $conf = $this->_conf;
        $selected = $this->_name;
        $files = $this->_files;
        $list0 = [];
        $btns = [];
        if ($this->_base != $this->_home) {
            $btns[] = [
                'text' => '<i class="bi bi-chevron-double-left"></i>',
                'url' => dirname($this->_base),
                'title' => 'Up Level',
                'selected' => false,
            ];
        }
        foreach ($conf->list as $name => $v) {
            if (!$conf->hidden($name) && $this->hasPriv($name)) {
                $v['isDir'] = $this->isDir($name);
                if (isset($v[Config::BUTTON])) {
                    $btns[] = $this->makeButton($name, $v);
                } else {
                    $list0[] = $this->makeItem($name, $v, $selected);
                }
            }
        }
        $list1 = [];
        foreach ($files as $name => $file) {
            $item = $this->makeItem($name, $file, $selected);
            $item['time'] = $file['time'] ?? 0;
            $list1[] = $item;
        }
        if ($conf->order) {
            usort($list1, $conf->order);
        }
        return ['buttons' => $btns, 'files' => array_merge($list0, $list1)];
    }

    private function createFileList()
    {
        $conf = $this->_conf;
        $this->_files = [];
        if (!$conf->editable) {
            return;
        }
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
            $res = Arr::transKeys($file, 'title', 'time', 'uid', 'uname');
            if (!empty($res)) {
                $json[$name] = $res;
            }
        }
        $path = $this->_path;
        if (!is_dir($path)) {
            mkdir($path, 0775, true);
        }
        file_put_contents(
            $path . DS . App::META_FILE,
            json_encode($json, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . PHP_EOL
        );
    }

    private function isDir($name)
    {
        $f = $this->_conf->dirOrFile($name);
        return $f ?? is_dir($this->_path . DS . $name);
    }

    private function hasPriv($name, $key = Server::GET)
    {
        $user = Sys::user();
        if ($user->hasPriv(User::ADMIN)) {
            return true;
        }
        $conf = $this->_conf;
        $priv = $conf->priv($name, $key);
        foreach ($priv as $p) {
            if ($p === User::OWNER) {
                $meta = $this->_files[$name];
                if (isset($meta) && $user->id === $meta['uid']) {
                    continue;
                }
                return false;
            }
            if (!$user->hasPriv($p)) {
                return false;
            }
        }
        return true;
    }

    private function metaView()
    {
        if (!$this->_conf->editable) {
            return '';
        }
        $files = $this->_files;
        $name = $this->_name;
        if (!array_key_exists($name, $files)) {
            return '';
        }
        $meta = $files[$name];
        if (!isset($meta)) {
            return '';
        }
        return View::renderHtml('meta', [
            'name' => $name,
            'uname' => $meta['uname'],
            'time' => $meta['time'],
            'edit' => $this->hasPriv($name, Server::PUT),
            'delete' => $this->hasPriv($name, Server::AJAX_DELETE),
            'accept' => $this->_conf->accept,
            'sizeLimit' => $this->_conf->sizeLimit,
        ]);
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
