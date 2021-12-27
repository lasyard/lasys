<?php
final class App
{
    public const META_FILE = 'list.json';

    private $_home;
    private $_datum = [];
    private $_scripts = [];
    private $_styles = [];
    private $_base;
    private $_path;
    private $_conf;
    private $_name;
    private $_files;
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
        list($this->_home, $args, $type) = Server::getHomeAndPath();
        define('PUB_URL', $this->_home . Str::pathUrl(PUB_DIR) . '/');
        session_cache_limiter('nocache');
        session_start();
        $this->_base = $this->_home;
        $this->_path = DATA_PATH;
        $this->_conf = new Config(CONF_PATH);
        $title = null;
        $action = null;
        $breadcrumbs = [];
        while (!empty($args)) {
            $name = array_shift($args);
            if ($name == '') {
                continue;
            }
            $this->_name = $name;
            if ($this->isDir($name)) {
                if ($this->_base != $this->_home) {
                    $breadcrumbs[] = Html::link($title, $this->_base);
                }
                $this->_path .= DS . $name;
                $this->_base .= $name . '/';
                $title = $this->_conf->title($name);
                if ($this->hasPrivOf($name, Server::GET)) {
                    $this->_conf->shift($name);
                } else {
                    $action = Actions::error('You do not have privilege to view "' . $name . '".');
                    break;
                }
            } else {
                $action = $this->action($this->_name, $type);
                break;
            }
        }
        if ($action === null) {
            $this->_name = $this->_conf->get(Config::DEFAULT_ITEM);
            $action = $this->action($this->_name, $type);
        }
        $httpHeaders = $action[Actions::ACTION]->httpHeaders;
        if ($type == Server::HEAD) {
            // Seems never run to this.
            $this->header($httpHeaders);
            exit;
        }
        // This is needed when calling action->do, e.g., for file uploading.
        $this->createFileList();
        if ($this->hasPriv($this->_name, $action[Actions::PRIV])) {
            $action[Actions::ACTION]->do($args, $this->_base, $this->_path, $this->_name);
        } else {
            $action[Actions::ACTION]->doError('You do not have privilege to do this.');
        }
        if (Server::isAjax($type)) {
            echo $action->content;
            exit;
        }
        $title = APP_TITLE;
        $subTitle = $action->title ?? $this->_conf->title($this->_name);
        if ($subTitle) {
            $title .= $subTitle;
        }
        $this->addScript('js' . DS . 'main');
        $this->addStyle('css' . DS . 'main');
        $this->addStyle('lib' . DS . 'bootstrap-icons');
        $list = $this->createItemList();
        $this->_vars = [
            'home' => $this->_home,
            'title' => $title,
            'datum' => $this->_datum,
            'scripts' => $this->_scripts,
            'styles' => $this->_styles,
            'base' => $this->_base,
            'breadcrumbs' => $breadcrumbs,
            'buttons' => $list['buttons'],
            'files' => $list['files'],
            'content' => $action[Actions::ACTION]->content,
        ];
        $this->header($httpHeaders);
        $this->view('main');
    }

    private function action($name, $type)
    {
        $conf = $this->_conf;
        return $conf->action($name, $type)
            ?? FileActions::action($conf->get(Config::READ_ONLY), $type)
            ?? Actions::default()->priv();
    }

    private function header($httpHeaders)
    {
        if (isset($httpHeaders)) {
            foreach ($httpHeaders as $header) {
                header($header);
            }
        }
    }

    private function makeButton($name, $info)
    {
        return Html::link($info[Config::BUTTON], $this->_base . $name, $info[Config::TITLE] ?? Str::captalize($name));
    }

    private function makeItem($name, $info)
    {
        $title = $info[Config::TITLE] ?? Str::captalize(pathinfo($name, PATHINFO_FILENAME));
        $li = ($name === $this->_name) ? '<li class="highlighted">' : '<li>';
        $li .= Html::link($title, $this->_base . $name . ($info['isDir'] ? '/' : ''));
        $li .= $info['isDir'] ? Icon::FOLDER : '';
        $li .= '</li>';
        return [
            'name' => $title,
            'time' => $info['time'] ?? 0,
            'li' => $li,
        ];
    }

    private function createItemList()
    {
        $conf = $this->_conf;
        $files = $this->_files;
        $list0 = [];
        $buttons = [];
        if ($this->_base != $this->_home) {
            $buttons[] = Html::link(Icon::UPPER_LEVEL, dirname($this->_base), 'Upper Level');
        }
        foreach ($conf->list() as $name => $info) {
            if ($conf->hidden($name) || !$this->hasPrivOf($name, Server::GET)) {
                continue;
            }
            if (isset($info[Config::BUTTON])) {
                $buttons[] = $this->makeButton($name, $info);
            } else {
                $info['isDir'] = $this->isDir($name);
                $list0[] = $this->makeItem($name, $info);
            }
        }
        $list1 = [];
        foreach ($files as $name => $file) {
            $list1[] = $this->makeItem($name, $file);
        }
        $order = $conf->get(FileActions::ORDER);
        if ($order) {
            usort($list1, $order);
        }
        return ['buttons' => $buttons, 'files' => array_column(array_merge($list0, $list1), 'li')];
    }

    private function createFileList()
    {
        $conf = $this->_conf;
        $this->_files = [];
        if ($conf->get(Config::READ_ONLY)) {
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

    public function hasPriv($name, $priv)
    {
        $user = Sys::user();
        if ($user->hasPriv(User::ADMIN)) {
            return true;
        }
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

    public function hasPrivOf($name, $type)
    {
        $actions = $this->action($name, $type);
        if ($actions) {
            return $this->hasPriv($name, $actions[Actions::PRIV]);
        }
        return false;
    }

    public function conf($name)
    {
        return $this->_conf->get($name);
    }

    public function info($name)
    {
        $files = $this->_files;
        if (array_key_exists($name, $files)) {
            $meta = $files[$name];
            if (isset($meta)) {
                return $meta;
            }
        }
        return null;
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
        $this->_datum[] = 'const ' . $name . ' = ' . json_encode($data, $flags) . ';';
    }

    public function redirect($name)
    {
        $url = (strpos($name, '//') === false) ? $this->_base . $name : $name;
        header('Location: ' . $url);
        exit;
    }
}
